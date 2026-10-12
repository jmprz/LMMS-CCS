<?php

namespace App\Console\Commands;

use App\Services\RiskDatasetReportService;
use Illuminate\Console\Command;

class ReportRiskDataset extends Command
{
    protected $signature = 'risk:dataset-report {csv-path : Local risk dataset CSV path}
                            {--output= : Optional completeness CSV filename under storage/app/risk-datasets}';

    protected $description = 'Report risk dataset completeness and numeric statistics without exposing individual students';

    public function handle(RiskDatasetReportService $service): int
    {
        try {
            $report = $service->analyze($this->argument('csv-path'));
            $this->table(['Dataset measure', 'Count'], [
                ['Student-class rows', $report['row_count']],
                ['Unique students', $report['unique_students']],
                ['Unique classes', $report['unique_classes']],
                ['Unique student-class pairs', $report['unique_student_class_pairs']],
                ['Duplicate pair rows', $report['duplicate_pair_rows']],
                ['Distinct non-missing feature cutoffs', $report['cutoff_count']],
            ]);
            $this->line('Retained features (SD is sample standard deviation; numeric stats exclude missing and invalid values):');
            foreach ([...array_keys($report['feature_groups']), 'Supporting Metadata'] as $group) {
                $selected = array_filter($report['features'], fn ($feature) => app(\App\Services\RiskFeatureSchema::class)->group($feature['feature']) === $group);
                $this->line($group.':');
                $this->table(
                    ['CSV feature', 'Present', 'Missing', 'Complete %', 'Numeric', 'Invalid', 'Min', 'Max', 'Mean', 'Median', 'SD'],
                    array_map(fn ($feature) => [
                        $feature['source_column'], $feature['non_missing'], $feature['missing'],
                        $this->number($feature['completeness_percent']), $feature['numeric_count'], $feature['invalid_numeric'],
                        $this->number($feature['min']), $this->number($feature['max']), $this->number($feature['mean']),
                        $this->number($feature['median']), $this->number($feature['sample_stddev']),
                    ], array_values($selected))
                );
            }
            $this->line('Excluded predictors (not part of core readiness):');
            $this->table(['Variable', 'Methodological note'], collect($report['excluded_predictors'])->map(fn ($note, $feature) => [$feature, $note])->values()->all());
            $this->line('Class summaries (cell completeness includes all detected features and supporting counts):');
            $this->table(['Class', 'Rows', 'Unique students', 'Complete cells %'], array_map(fn ($class) => [
                $class['lab_session_id'], $class['row_count'], $class['unique_students'], $this->number($class['completeness_percent']),
            ], $report['classes']));
            foreach ($report['classes'] as $class) {
                $this->line('Completeness for class '.$class['lab_session_id'].':');
                $this->table(['CSV feature', 'Present', 'Missing', 'Complete %', 'Invalid numeric'], array_map(fn ($feature) => [
                    $feature['source_column'], $feature['non_missing'], $feature['missing'],
                    $this->number($feature['completeness_percent']), $feature['invalid_numeric'],
                ], array_values($class['features'])));
            }
            foreach ($report['features'] as $feature) {
                if ($report['row_count'] > 0 && $feature['non_missing'] === 0) {
                    $this->warn($feature['source_column'].': 100% missing; unavailable in this dataset.');
                } elseif ($feature['numeric_count'] > 0 && $feature['min'] === $feature['max']) {
                    $this->warn($feature['source_column'].': constant among available numeric values; completeness alone does not establish usefulness.');
                }
                if ($feature['invalid_numeric'] > 0) {
                    $this->warn($feature['source_column'].': contains non-numeric values; inspect the source before use.');
                }
            }
            if ($report['absent_features'] !== []) {
                $this->warn('Features absent from CSV schema: '.implode(', ', $report['absent_features']));
            }
            $this->line('Topic performance coverage (tagging counts describe class content, not student scores):');
            $this->table(['Scope', 'Class', 'Kind', 'Student-class rows with score', 'Complete %', 'Unique students with score', 'Tagged items', 'Untagged items'],
                array_map(fn ($row) => [$row['scope'], $row['lab_session_id'] ?? 'All', $row['kind'],
                    $row['student_class_rows_with_score'], $this->number($row['completeness_percent']), $row['unique_students_with_score'],
                    $row['tagged_items'] ?? 'Unavailable', $row['untagged_items'] ?? 'Unavailable'], $report['topic_coverage']));
            $this->line('Topic scalar encoding remains undecided. Archived CSVs may lack topic/task tagging data.');
            $this->line('Topic columns use all student-class rows as their denominator; absence can also mean that a topic does not apply to a class.');
            if ($report['duplicate_pair_rows'] > 0) {
                $this->warn('Duplicate student-class rows detected; counts describe file rows without silently removing duplicates.');
            }
            if ($report['cutoff_count'] !== 1) {
                $this->warn('Dataset does not have exactly one observed non-missing cutoff.');
            }
            $this->line('For later train/test splitting, group by student so the same student cannot appear in both sets.');
            $this->line('No labels are generated. CSV reporting cannot independently verify exclusion of simulated source attempts.');
            if ($this->option('output') !== null) {
                foreach ($service->export($report, $this->option('output')) as $path) {
                    $this->info('Saved '.$path);
                }
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function number(?float $value): string
    {
        return $value === null ? 'N/A' : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
