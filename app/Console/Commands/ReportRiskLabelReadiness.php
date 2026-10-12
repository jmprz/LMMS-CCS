<?php

namespace App\Console\Commands;

use App\Services\RiskLabelDatasetService;
use Illuminate\Console\Command;

class ReportRiskLabelReadiness extends Command
{
    protected $signature = 'risk:label-readiness {feature-csv? : Explicit ML-ready feature CSV; defaults to latest export}';

    protected $description = 'Report aggregate official-outcome join coverage, timing and target readiness without student identities';

    public function handle(RiskLabelDatasetService $service): int
    {
        try {
            $path = $this->argument('feature-csv') ?? $service->latestFeaturePath();
            $report = $service->inspect($path)['summary'];
            $this->line('Feature dataset: '.basename($path));
            $this->table(['Measure', 'Value'], collect($report)->except(['classes', 'criterion', 'target_balance'])
                ->map(fn ($value, $key) => [$key, is_array($value) ? implode(', ', $value) : ($value ?? 'Unavailable')])->values()->all());
            $this->table(['Class', 'Feature rows', 'Official outcomes', 'Matched', 'Coverage %', 'At-Risk', 'Not At-Risk', 'At-Risk %', 'Not At-Risk %'],
                array_map(fn ($row) => array_map(fn ($value) => $value ?? 'Unavailable', array_values($row)), $report['classes']));
            if ($report['criterion']) {
                $this->line('Configured criterion: '.$report['criterion']['passing_operator'].' '.$report['criterion']['passing_grade']);
                $this->table(['Target balance among official outcomes', 'Value'], collect($report['target_balance'])->map(fn ($value, $key) => [$key, $value ?? 'Unavailable'])->values()->all());
                if ($report['official_outcomes'] && (! $report['target_balance']['at_risk'] || ! $report['target_balance']['not_at_risk'])) {
                    $this->warn('Only one target class is represented.');
                }
            } else {
                $this->warn('Passing criterion unconfigured or invalid: Academic Risk Status distribution is not computed.');
            }
            foreach ($report['classes'] as $class) {
                if (! $class['outcomes']) {
                    $this->warn('Class '.$class['lab_session_id'].' has no official outcomes.');
                } elseif ($class['outcomes'] < config('risk_target.small_count_warning')) {
                    $this->warn('Class '.$class['lab_session_id'].' has very few official outcomes.');
                }
                if ($report['criterion'] && $class['outcomes']) {
                    foreach (['at_risk', 'not_at_risk'] as $label) {
                        if ($class[$label] < config('risk_target.small_count_warning')) {
                            $this->warn('Class '.$class['lab_session_id'].' has a small '.$label.' count.');
                        }
                    }
                }
            }
            if ($report['duplicate_feature_pairs'] || $report['duplicate_outcome_pairs'] || $report['duplicate_final_record_pairs']) {
                $this->warn('Duplicate pair keys require review and block labeled export.');
            }
            if ($report['outcome_before_cutoff'] || $report['outcome_at_cutoff'] || $report['missing_outcome_timestamp'] || $report['future_outcome_timestamp']) {
                $this->warn('Suspicious/missing recording timestamps require review and block labeled export.');
            }
            $this->line('recorded_at is database recording time, not the academic event date. Later entry alone does not prove an outcome occurred after observation; verify official provenance separately.');
            $this->line('No labels are persisted, no imputation is performed, and no models are trained.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
