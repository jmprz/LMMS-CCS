<?php

namespace App\Console\Commands;

use App\Services\RiskSourceDataAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class AuditRiskSourceData extends Command
{
    protected $signature = 'risk:data-audit
                            {--classes= : Comma-separated lab session IDs; defaults to active enrolled classes}
                            {--cutoff= : Shared cutoff YYYY-MM-DD HH:MM:SS in application timezone; defaults to now}
                            {--output= : Optional audit CSV filename under storage/app/risk-datasets}';

    protected $description = 'Audit source availability and duration anomalies without changing risk features';

    public function handle(RiskSourceDataAuditService $service): int
    {
        try {
            $value = $this->option('cutoff');
            $cutoff = now()->startOfSecond();
            if ($value !== null) {
                if (! $value || ! Carbon::canBeCreatedFromFormat($value, 'Y-m-d H:i:s')) {
                    throw new InvalidArgumentException('--cutoff must use YYYY-MM-DD HH:MM:SS.');
                }
                $cutoff = Carbon::createFromFormat('Y-m-d H:i:s', $value, config('app.timezone'))->startOfSecond();
                if ($cutoff->format('Y-m-d H:i:s') !== $value || $cutoff->isFuture()) {
                    throw new InvalidArgumentException('Cutoff must be a valid prediction point at or before now.');
                }
            }
            $ids = null;
            if ($this->option('classes') !== null) {
                $ids = [];
                foreach (explode(',', $this->option('classes')) as $id) {
                    $parsed = filter_var(trim($id), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if ($parsed === false) {
                        throw new InvalidArgumentException('--classes requires positive integer IDs.');
                    }
                    $ids[] = $parsed;
                }
            }
            $report = $service->audit($ids, $cutoff);
            $this->info('Shared cutoff: '.$report['cutoff'].' ('.$report['timezone'].'); classes: '.$report['class_count']);
            $this->line('Source rows are restricted to students enrolled by cutoff. Current totals and pivot counters are explicitly marked.');
            foreach ($report['classes'] as $class) {
                $this->info('Class '.$class['lab_session_id'].' / '.$class['class_code'].' / '.$class['subject_name']);
                $this->showScope($class);
            }
            $this->info('Overall selected classes (student counts are distinct people; pairs and class dates are counted separately)');
            $this->showScope($report['overall']);
            $this->line('Topic scalar encoding is pending; this audit does not define topic task predictors.');
            $this->line('Duration flags >8 hours / >24 hours are inspection thresholds only; no values are deleted or capped.');
            $this->line('Material logs: legacy duration is client wall-clock time; visible_v1 is a bounded visible/recent-activity proxy. External viewing is not timed.');
            $this->line('Unclosed material sessions stay unclosed; missing duration is never reconstructed from opened_at/closed_at. Repeated open pairs are audit candidates, not proven duplicates.');
            $this->line('Submission tracking currently records submission time minus task creation time. Task-age matches are reported; this is not measured active work time.');
            $this->line('Raw source durations and v2-available durations are separate; current metadata/counters cannot reconstruct historical revisions.');
            $this->line('No student identities, activity content, blocked URLs, grade values, labels or model predictions are reported.');
            if ($this->option('output') !== null) {
                $this->info('Saved '.$service->export($report, $this->option('output')));
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function showScope(array $scope): void
    {
        $this->table(['Source measure', 'Value'], collect($scope['metrics'])->map(fn ($value, $key) => [$key, $this->format($value)])->values()->all());
        foreach (['attendance_statuses' => 'Attendance status distribution', 'current_pivot_violation_distribution' => 'Current pivot violation count distribution (not historical)'] as $key => $title) {
            $this->line($title);
            $this->table(['Value', 'Rows'], collect($scope[$key])->map(fn ($count, $value) => [$value, $count])->values()->all());
        }
        $this->line('Actual grading periods');
        $this->table(['Period', 'Records', 'Students', 'Recorded by cutoff'], $scope['grading_periods']);
        $this->line('Topic quiz coverage');
        $this->table(['Topic ID', 'Topic name', 'Quizzes', 'Real attempts', 'Students'], $scope['topics']);
        $this->line('Historical activity durations by log type (all excluded from v5 duration; counts remain event counts)');
        $this->table(['Type', 'Logs', 'Duration count', 'NULL', 'Negative', 'Min', 'Median', 'Mean', 'Max', '>8h', '>24h', 'No end', 'Positive duration / unchanged timestamp'],
            array_map(fn ($row) => array_map(fn ($value) => $this->format($value), array_values($row)), $scope['activity_types']));
        if ($scope['metrics']['subject_grade_records_current'] === 0) {
            $this->warn('Current academic grade unavailable: no subject_grades records for this enrollment population.');
        } elseif ($scope['metrics']['students_with_current_academic_grade_source'] === 0) {
            $this->warn('No usable current grade: v2 requires a non-null midterm grade with recorded_at, created_at and updated_at at/before cutoff. Inspect actual periods and timing above.');
        }
        if ($scope['metrics']['quizzes_topic_tagged_by_cutoff'] === 0) {
            $this->warn('No cutoff-eligible quizzes have learning_topic_id; topic quiz columns cannot be produced.');
        }
        if ($scope['metrics']['violation_feature_zero_variance'] === 1) {
            $this->warn('Violation feature has zero variance in this population.');
        }
    }

    private function format(mixed $value): string
    {
        if ($value === null) {
            return 'N/A';
        }

        return is_float($value) ? rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') : (string) $value;
    }
}
