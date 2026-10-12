<?php

namespace Tests\Feature;

use App\Services\RiskDatasetReportService;
use Tests\TestCase;

class RiskDatasetReportTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    private function csv(array $header, array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'risk-report-');
        $this->files[] = $path;
        $stream = fopen($path, 'wb');
        fputcsv($stream, $header, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, $row, ',', '"', '');
        }
        fclose($stream);

        return $path;
    }

    public function test_ml_group_keys_count_students_without_identities_and_exclusions_are_separate(): void
    {
        $key = str_repeat('a', 64);
        $path = $this->csv(['student_group_key', 'lab_session_id', 'quiz_avg', 'attendance_rate', 'current_academic_grade', 'violation_count', 'topic_quiz_performance_json'],
            [[$key, 1, 80, '', 1.25, 3, '{}'], [$key, 2, 90, '', 1.25, 3, '{}']]);
        $report = app(RiskDatasetReportService::class)->analyze($path);
        $this->assertSame(2, $report['row_count']);
        $this->assertSame(1, $report['unique_students']);
        $this->assertSame(2, $report['unique_classes']);
        $this->assertSame(0, $report['features']['attendance_rate']['non_missing']);
        $this->assertArrayNotHasKey('current_academic_grade', $report['features']);
        $this->assertArrayNotHasKey('violation_count', $report['features']);
        $this->assertSame(config('risk_features.excluded'), $report['excluded_predictors']);
        $this->assertArrayHasKey('Academic Performance', $report['feature_groups']);
        $this->assertArrayHasKey('Optional Attendance', $report['feature_groups']);
        $this->assertStringNotContainsString($key, json_encode($report));
        $this->artisan('risk:dataset-report', ['csv-path' => $path])
            ->expectsOutputToContain('Excluded predictors')
            ->expectsOutputToContain('complete academic grades are not fully observable')
            ->expectsOutputToContain('primarily online/asynchronous')
            ->doesntExpectOutputToContain($key)->assertSuccessful();
    }

    private function fixture(): string
    {
        return $this->csv([
            'user_id', 'lab_session_id', 'student_name', 'class_code', 'feature_cutoff_at',
            'quiz_avg', 'task_avg', 'materials_accessed', 'topic_task_average',
            'total_material_engagement_time', 'topic_quiz_average__topic_7',
        ], [
            [900001, 1, 'Sensitive Person', 'CLASS-1', '2026-10-01 23:59:59', 10, '', 0, '', 60, 50],
            [900002, 1, 'Private Name', 'CLASS-1', '2026-10-01 23:59:59', 20, 'NULL', 2, '', 120, ''],
            [900001, 2, 'Sensitive Person', 'CLASS-2', '2026-10-01 23:59:59', 30, 'N/A', 4, '', 180, ''],
            [900003, 2, 'Another Name', 'CLASS-2', '2026-10-01 23:59:59', '', '   ', 0, '', 0, ''],
        ]);
    }

    public function test_json_topic_coverage_counts_distinct_students_and_class_content_once(): void
    {
        $quiz = json_encode((object) [7 => ['topic_name' => 'Loops', 'average' => 80, 'attempt_count' => 1]]);
        $task = json_encode((object) [7 => ['topic_name' => 'Loops', 'average' => 60, 'graded_task_count' => 2]]);
        $path = $this->csv(['user_id', 'lab_session_id', 'quiz_avg', 'topic_quiz_performance_json', 'topic_task_performance_json',
            'tagged_quiz_count', 'untagged_quiz_count', 'tagged_task_count', 'untagged_task_count'], [
            [900001, 1, 80, $quiz, $task, 2, 1, 1, 2],
            [900002, 1, '', '{}', '{}', 2, 1, 1, 2],
            [900001, 2, 80, $quiz, '{}', 1, 0, 0, 1],
            [900001, 2, 80, $quiz, '{}', 1, 0, 0, 1],
        ]);
        $service = app(RiskDatasetReportService::class);
        $report = $service->analyze($path);
        $quizCoverage = collect($report['topic_coverage'])->first(fn ($row) => $row['scope'] === 'overall' && $row['kind'] === 'quiz');
        $this->assertSame(2, $quizCoverage['student_class_rows_with_score']);
        $this->assertSame(1, $quizCoverage['unique_students_with_score']);
        $this->assertSame(3, $quizCoverage['tagged_items']);
        $this->assertSame(1, $quizCoverage['untagged_items']);
        $taskCoverage = collect($report['topic_coverage'])->first(fn ($row) => $row['scope'] === 'overall' && $row['kind'] === 'task');
        $this->assertSame(1, $taskCoverage['unique_students_with_score']);
        $this->assertSame(1, $taskCoverage['tagged_items']);
        $this->assertSame(3, $taskCoverage['untagged_items']);
        $this->assertStringNotContainsString('900001', json_encode($report));
        $filename = 'test-topics-'.bin2hex(random_bytes(6)).'.csv';
        $paths = $service->export($report, $filename);
        $this->files = [...$this->files, ...$paths];
        $this->assertCount(3, $paths);
        $this->assertStringContainsString('unique_students_with_score', file_get_contents($paths[2]));
        $this->assertStringNotContainsString('900001', file_get_contents($paths[2]));
    }

    public function test_invalid_topic_json_and_inconsistent_class_counts_fail_without_echoing_data(): void
    {
        foreach (['private-data', '{"7":{"average":"private-data","attempt_count":1}}'] as $value) {
            $path = $this->csv(['user_id', 'lab_session_id', 'quiz_avg', 'topic_quiz_performance_json'], [[900001, 1, '', $value]]);
            try {
                app(RiskDatasetReportService::class)->analyze($path);
                $this->fail('Expected invalid JSON performance to fail.');
            } catch (\InvalidArgumentException $e) {
                $this->assertStringNotContainsString('private-data', $e->getMessage());
            }
        }
        $path = $this->csv(['user_id', 'lab_session_id', 'quiz_avg', 'tagged_quiz_count'], [[900001, 1, '', 1], [900002, 1, '', 2]]);
        $this->expectException(\InvalidArgumentException::class);
        app(RiskDatasetReportService::class)->analyze($path);
    }

    public function test_counts_missing_cells_numeric_statistics_and_class_completeness(): void
    {
        $report = app(RiskDatasetReportService::class)->analyze($this->fixture());
        $this->assertSame(4, $report['row_count']);
        $this->assertSame(3, $report['unique_students']);
        $this->assertSame(2, $report['unique_classes']);
        $this->assertSame(4, $report['unique_student_class_pairs']);
        $this->assertSame(0, $report['duplicate_pair_rows']);
        $this->assertSame(1, $report['cutoff_count']);
        $quiz = $report['features']['quiz_avg'];
        $this->assertSame(3, $quiz['non_missing']);
        $this->assertSame(1, $quiz['missing']);
        $this->assertSame(75.0, $quiz['completeness_percent']);
        $this->assertSame(25.0, $quiz['missing_percent']);
        $this->assertSame(10.0, $quiz['min']);
        $this->assertSame(30.0, $quiz['max']);
        $this->assertSame(20.0, $quiz['mean']);
        $this->assertSame(20.0, $quiz['median']);
        $this->assertSame(10.0, $quiz['sample_stddev']);
        $task = $report['features']['task_avg'];
        $this->assertSame(0, $task['non_missing']);
        $this->assertSame(4, $task['missing']);
        $this->assertSame(100.0, $task['missing_percent']);
        $this->assertNull($task['mean']);
        $this->assertNull($task['sample_stddev']);
        $this->assertSame(4, $report['features']['materials_accessed']['non_missing']);
        $this->assertSame(0.0, $report['features']['materials_accessed']['min']);
        $this->assertSame(1.0, $report['features']['materials_accessed']['median']);
        $this->assertSame('reading_total_seconds', $report['features']['total_material_engagement_time']['feature']);
        $this->assertSame('topic_quiz_average', $report['features']['topic_quiz_average__topic_7']['feature']);
        $this->assertSame([2, 2], array_column($report['classes'], 'row_count'));
        $this->assertSame(100.0, $report['classes'][0]['features']['quiz_avg']['completeness_percent']);
        $this->assertSame(50.0, $report['classes'][1]['features']['quiz_avg']['completeness_percent']);
        $this->assertSame(58.33, $report['classes'][0]['completeness_percent']);
        $this->assertSame(2, $report['classes'][1]['unique_students']);
        $serialized = json_encode($report);
        $this->assertStringNotContainsString('900001', $serialized);
        $this->assertStringNotContainsString('Sensitive Person', $serialized);
        $this->assertArrayNotHasKey('student_name', $report['features']);
    }

    public function test_command_exports_only_aggregates_and_reports_unavailable_features(): void
    {
        $filename = 'test-readiness-'.bin2hex(random_bytes(6)).'.csv';
        $overall = storage_path('app/risk-datasets/'.$filename);
        $classes = storage_path('app/risk-datasets/'.substr($filename, 0, -4).'-classes.csv');
        $this->files = [...$this->files, $overall, $classes];
        $this->artisan('risk:dataset-report', ['csv-path' => $this->fixture(), '--output' => $filename])
            ->expectsOutputToContain('topic_task_average: 100% missing')
            ->doesntExpectOutputToContain('Sensitive Person')
            ->doesntExpectOutputToContain('900001')
            ->assertSuccessful();
        foreach ([$overall, $classes] as $path) {
            $text = file_get_contents($path);
            $this->assertStringNotContainsString('student_name', $text);
            $this->assertStringNotContainsString('900001', $text);
            $this->assertStringNotContainsString('Sensitive Person', $text);
        }
        $stream = fopen($classes, 'rb');
        $header = fgetcsv($stream, escape: '');
        $rows = [];
        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = array_combine($header, $row);
        }
        fclose($stream);
        $quiz = array_values(array_filter($rows, fn ($row) => $row['source_column'] === 'quiz_avg'));
        $this->assertCount(2, $quiz);
        $this->assertSame('100', $quiz[0]['completeness_percent']);
        $this->assertSame('50', $quiz[1]['completeness_percent']);
        // Existing reports must not be overwritten.
        $original = file_get_contents($overall);
        $this->artisan('risk:dataset-report', ['csv-path' => $this->fixture(), '--output' => $filename])->assertFailed();
        $this->assertSame($original, file_get_contents($overall));
    }

    public function test_invalid_numeric_values_are_flagged_and_excluded_from_statistics(): void
    {
        $path = $this->csv(['user_id', 'lab_session_id', 'quiz_avg'], [
            [1, 1, 0], [2, 1, 'invalid'], [3, 1, '1e999'], [4, 1, 'NaN'], [5, 1, ''],
        ]);
        $feature = app(RiskDatasetReportService::class)->analyze($path)['features']['quiz_avg'];
        $this->assertSame(3, $feature['non_missing']);
        $this->assertSame(2, $feature['missing']);
        $this->assertSame(2, $feature['invalid_numeric']);
        $this->assertSame(1, $feature['numeric_count']);
        $this->assertSame(0.0, $feature['mean']);
        $this->assertNull($feature['sample_stddev']);
    }

    public function test_empty_dataset_and_blank_lines_do_not_invent_counts_or_statistics(): void
    {
        $path = $this->csv(['user_id', 'lab_session_id', 'quiz_avg'], []);
        file_put_contents($path, "\n\n", FILE_APPEND);
        $report = app(RiskDatasetReportService::class)->analyze($path);
        $this->assertSame(0, $report['row_count']);
        $this->assertSame(0, $report['unique_students']);
        $this->assertSame(0, $report['unique_classes']);
        $this->assertNull($report['features']['quiz_avg']['completeness_percent']);
        $this->assertNull($report['features']['quiz_avg']['median']);
    }

    public function test_bom_duplicate_pairs_and_even_median(): void
    {
        $path = $this->csv(["\xEF\xBB\xBFuser_id", 'lab_session_id', 'quiz_avg'], [[1, 1, 10], [1, 1, 20]]);
        $report = app(RiskDatasetReportService::class)->analyze($path);
        $this->assertSame(2, $report['row_count']);
        $this->assertSame(1, $report['unique_students']);
        $this->assertSame(1, $report['duplicate_pair_rows']);
        $this->assertSame(15.0, $report['features']['quiz_avg']['median']);
    }

    public function test_malformed_schema_rows_and_nonlocal_input_fail_without_echoing_cell_data(): void
    {
        $paths = [
            $this->csv(['user_id', 'lab_session_id', 'quiz_avg', 'quiz_avg'], []),
            $this->csv(['user_id', 'quiz_avg'], []),
            $this->csv(['user_id', 'lab_session_id', 'quiz_avg'], [[900001, 1]]),
            $this->csv(['user_id', 'lab_session_id', 'quiz_avg'], [['Sensitive Person', 1, 20]]),
            'php://memory',
        ];
        foreach ($paths as $path) {
            $this->artisan('risk:dataset-report', ['csv-path' => $path])
                ->doesntExpectOutputToContain('Sensitive Person')
                ->doesntExpectOutputToContain('900001')->assertFailed();
        }
        $this->artisan('risk:dataset-report', ['csv-path' => $this->fixture(), '--output' => '../escape.csv'])->assertFailed();
    }
}
