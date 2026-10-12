<?php

namespace Tests\Feature;

use App\Models\LabSession;
use App\Models\SubjectGrade;
use App\Models\User;
use App\Services\OfficialOutcomeService;
use App\Services\RiskFeatureSchema;
use App\Services\RiskLabelDatasetService;
use App\Services\RiskTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RiskLabelReadinessTest extends TestCase
{
    use RefreshDatabase;

    private User $professor;

    private LabSession $class;

    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Carbon::setTestNow('2026-10-03 16:00:00');
        config(['risk_features.grouping_secret' => 'test-group-key', 'risk_target.passing_grade' => null, 'risk_target.passing_operator' => 'gte']);
        $this->professor = $this->user('professor');
        $this->class = LabSession::create(['class_code' => 'LABEL', 'subject_name' => 'Test', 'faculty_id' => $this->professor->id]);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role = 'student'): User
    {
        $uuid = (string) Str::uuid();
        $user = User::findOrFail(DB::table('users')->insertGetId(['first_name' => 'Test', 'last_name' => $role, 'name' => 'Private Test Name',
            'school_id' => $uuid, 'email' => $uuid.'@example.test', 'role' => $role, 'password' => bcrypt('password')]));
        if ($role === 'student') {
            DB::table('class_student')->insert(['user_id' => $user->id, 'lab_session_id' => $this->class->id, 'joined_at' => now()->subDays(2)]);
        }

        return $user;
    }

    private function outcome(User $student, string $grade): void
    {
        $service = app(OfficialOutcomeService::class);
        $batch = $service->preview($this->professor, $this->class, "student_identifier,final_outcome\n".$student->school_id.','.$grade."\n", 'Official test record', 'test.csv');
        $service->commit($this->professor, $this->class, $batch->id);
    }

    private function csv(array $students, ?array $override = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'label-readiness-');
        $this->files[] = $path;
        $header = app(RiskFeatureSchema::class)->columns(true);
        $stream = fopen($path, 'w');
        fputcsv($stream, $header, escape: '');
        foreach ($students as $student) {
            $row = array_fill_keys($header, '');
            $row = [...$row, 'student_group_key' => app(RiskFeatureSchema::class)->groupingKey($student->id), 'lab_session_id' => (string) $this->class->id,
                'feature_version' => 'v5', 'feature_cutoff_at' => '2026-10-03 14:00:00',
                'topic_quiz_performance_json' => '{}', 'topic_task_performance_json' => '{}', ...($override ?? [])];
            fputcsv($stream, array_map(fn ($column) => $row[$column], $header), escape: '');
        }
        fclose($stream);

        return $path;
    }

    private function outputName(): string
    {
        $filename = 'test-labels-'.Str::uuid().'.csv';
        $this->files[] = storage_path('app/risk-datasets/'.$filename);
        $this->files[] = storage_path('app/risk-datasets/'.substr($filename, 0, -4).'.schema.json');

        return $filename;
    }

    public function test_readiness_matches_pairs_and_reports_both_unmatched_sides_without_pii(): void
    {
        $matched = $this->user();
        $without = $this->user();
        $outside = $this->user();
        $this->outcome($matched, '2.50');
        $this->outcome($outside, '3.00');
        $path = $this->csv([$matched, $without]);
        $summary = app(RiskLabelDatasetService::class)->inspect($path)['summary'];
        $this->assertSame(2, $summary['feature_rows']);
        $this->assertSame(2, $summary['feature_students']);
        $this->assertSame(1, $summary['matched_rows']);
        $this->assertSame(1, $summary['feature_rows_without_outcomes']);
        $this->assertSame(1, $summary['outcomes_without_features']);
        $this->assertNull($summary['target_balance']);
        $this->assertEquals(50, $summary['classes'][0]['match_coverage_percent']);
        $this->assertStringNotContainsString($matched->school_id, json_encode($summary));
        $this->assertStringNotContainsString(app(RiskFeatureSchema::class)->groupingKey($matched->id), json_encode($summary));
        $this->artisan('risk:label-readiness', ['feature-csv' => $path])->expectsOutputToContain('Passing criterion unconfigured')
            ->doesntExpectOutputToContain('Private Test Name')->doesntExpectOutputToContain($matched->school_id)->assertSuccessful();
    }

    public function test_export_requires_criterion_before_creating_any_file(): void
    {
        $path = $this->csv([$this->user()]);
        $filename = $this->outputName();
        $this->artisan('risk:export-labeled-dataset', ['feature-csv' => $path, '--output' => $filename])
            ->expectsOutputToContain('No passing threshold is assumed')->assertFailed();
        $this->assertFileDoesNotExist(storage_path('app/risk-datasets/'.$filename));
    }

    public function test_configured_labels_boundary_missing_values_and_leakage_categories(): void
    {
        config(['risk_target.passing_grade' => '3.00']);
        $below = $this->user();
        $boundary = $this->user();
        $unmatched = $this->user();
        $this->outcome($below, '2.50');
        $this->outcome($boundary, '3.00');
        $path = $this->csv([$below, $boundary, $unmatched]);
        $filename = $this->outputName();
        $this->artisan('risk:export-labeled-dataset', ['feature-csv' => $path, '--output' => $filename])->assertSuccessful();
        $stream = fopen(storage_path('app/risk-datasets/'.$filename), 'r');
        $header = fgetcsv($stream, escape: '');
        $rows = [];
        while (($values = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = array_combine($header, $values);
        } fclose($stream);
        $this->assertCount(2, $rows);
        $this->assertSame(['final_outcome', 'academic_risk_status'], array_slice($header, -2));
        $this->assertSame(['at_risk', 'not_at_risk'], array_column($rows, 'academic_risk_status'));
        $this->assertSame('', $rows[0]['quiz_avg']);
        $this->assertSame('', $rows[0]['activity_duration_seconds']);
        $this->assertSame('', $rows[0]['attendance_rate']);
        $this->assertSame('{}', $rows[0]['topic_task_performance_json']);
        $this->assertSame(app(RiskFeatureSchema::class)->groupingKey($below->id), $rows[0]['student_group_key']);
        foreach (['user_id', 'email', 'school_id', 'name'] as $column) {
            $this->assertNotContains($column, $header);
        }
        $this->assertStringNotContainsString($below->school_id, file_get_contents(storage_path('app/risk-datasets/'.$filename)));
        $schema = app(RiskTargetService::class)->categories();
        foreach (['student_group_key', 'lab_session_id', 'final_outcome', 'academic_risk_status'] as $column) {
            $this->assertNotContains($column, [...$schema['model_x_core'], ...$schema['model_x_optional'], ...$schema['structured_topic_encoding_pending']]);
        }
        $this->assertSame(['academic_risk_status'], $schema['target_y']);
        $this->assertSame(['final_outcome'], $schema['ground_truth_source']);
        $summary = app(RiskLabelDatasetService::class)->inspect($path)['summary'];
        $this->assertEquals(50, $summary['target_balance']['at_risk_percent']);
        $this->assertSame(1, $summary['target_balance']['not_at_risk']);
        $this->assertFileExists(storage_path('app/risk-datasets/'.substr($filename, 0, -4).'.schema.json'));
        $this->artisan('risk:export-labeled-dataset', ['feature-csv' => $path, '--output' => $filename])->assertFailed();
    }

    public function test_duplicate_feature_keys_are_reported_and_export_is_blocked(): void
    {
        $student = $this->user();
        $this->outcome($student, '2.50');
        $path = $this->csv([$student, $student]);
        $summary = app(RiskLabelDatasetService::class)->inspect($path)['summary'];
        $this->assertSame(1, $summary['duplicate_feature_pairs']);
        $this->assertSame(1, $summary['duplicate_feature_rows']);
        config(['risk_target.passing_grade' => '3.00']);
        $this->artisan('risk:export-labeled-dataset', ['feature-csv' => $path, '--output' => $this->outputName()])->assertFailed();
    }

    public function test_latest_imported_revision_is_used_and_duplicate_final_pairs_are_detected(): void
    {
        $student = $this->user();
        $this->outcome($student, '2.50');
        $this->outcome($student, '3.00');
        config(['risk_target.passing_grade' => '3.00']);
        $path = $this->csv([$student]);
        $report = app(RiskLabelDatasetService::class)->inspect($path);
        $this->assertEquals(3, $report['matches'][0]['outcome']['final_outcome']);
        $this->assertSame(2, $report['summary']['imported_revision_count']);
        $this->assertSame(1, $report['summary']['target_balance']['not_at_risk']);
        $grade = SubjectGrade::create(['user_id' => $student->id, 'lab_session_id' => $this->class->id, 'grading_period' => ' FINAL ', 'grade' => '2.50']);
        DB::table('subject_grade_revisions')->insert(['subject_grade_id' => $grade->id, 'grade' => '2.50', 'import_id' => DB::table('academic_outcome_imports')->value('id'), 'recorded_at' => now()]);
        $summary = app(RiskLabelDatasetService::class)->inspect($path)['summary'];
        $this->assertSame(1, $summary['duplicate_outcome_pairs']);
        $this->assertSame(1, $summary['duplicate_final_record_pairs']);
        $this->assertSame(1, $summary['ambiguous_feature_rows']);
        $this->artisan('risk:export-labeled-dataset', ['feature-csv' => $path, '--output' => $this->outputName()])->assertFailed();
    }

    public function test_timestamp_order_missing_and_future_cases_are_reported_without_pii(): void
    {
        $student = $this->user();
        $this->outcome($student, '3.00');
        $path = $this->csv([$student]);
        foreach ([null => 'missing_outcome_timestamp', '2026-10-03 13:00:00' => 'outcome_before_cutoff', '2026-10-03 14:00:00' => 'outcome_at_cutoff', '2026-10-04 16:00:00' => 'future_outcome_timestamp'] as $date => $metric) {
            DB::table('subject_grade_revisions')->update(['recorded_at' => $date ?: null]);
            $this->assertSame(1, app(RiskLabelDatasetService::class)->inspect($path)['summary'][$metric]);
            config(['risk_target.passing_grade' => '3.00']);
            $this->artisan('risk:export-labeled-dataset', ['feature-csv' => $path, '--output' => $this->outputName()])->assertFailed();
        }
    }

    public function test_invalid_criterion_and_unapproved_csv_columns_fail_without_pii(): void
    {
        config(['risk_target.passing_grade' => 'invalid']);
        $path = $this->csv([$this->user()]);
        $this->assertNotNull(app(RiskLabelDatasetService::class)->inspect($path)['summary']['criterion_error']);
        $this->artisan('risk:export-labeled-dataset', ['feature-csv' => $path, '--output' => $this->outputName()])->assertFailed();
        config(['risk_target.passing_grade' => '3.00']);
        $lines = file($path);
        $lines[0] = rtrim($lines[0]).",email\n";
        $lines[1] = rtrim($lines[1]).",private@example.test\n";
        file_put_contents($path, implode('', $lines));
        $this->artisan('risk:label-readiness', ['feature-csv' => $path])->doesntExpectOutputToContain('private@example.test')->assertFailed();
        $this->artisan('risk:export-labeled-dataset', ['feature-csv' => $path, '--output' => $this->outputName()])->assertFailed();
        config(['risk_target.passing_operator' => 'lte']);
        $this->assertSame('not_at_risk', app(RiskTargetService::class)->label('2.00'));
        $this->assertSame('at_risk', app(RiskTargetService::class)->label('4.00'));
    }
}
