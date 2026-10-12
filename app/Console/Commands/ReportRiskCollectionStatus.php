<?php

namespace App\Console\Commands;

use App\Services\RiskCollectionStatusService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ReportRiskCollectionStatus extends Command
{
    protected $signature = 'risk:collection-status {--classes= : Optional comma-separated class IDs; defaults to active enrolled classes}
        {--output= : Optional collection CSV filename under storage/app/risk-datasets} {--json : Print aggregate JSON rather than tables}';

    protected $description = 'Read-only collection monitoring for frozen v5 predictors and official outcomes; no risk calculation';

    public function handle(RiskCollectionStatusService $service): int
    {
        try {
            $ids = null;
            if ($this->option('classes') !== null) {
                $ids = [];
                foreach (explode(',', $this->option('classes')) as $id) {
                    $value = filter_var(trim($id), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if ($value === false) {
                        throw new InvalidArgumentException('--classes must contain positive class IDs.');
                    }
                    $ids[] = $value;
                }
            }
            $report = $service->report($ids);
            $path = $this->option('output') !== null ? $service->export($report, $this->option('output')) : null;
            if ($this->option('json')) {
                $this->line(json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }
            $this->line('Shared collection cutoff: '.$report['cutoff'].' ('.$report['timezone'].')');
            foreach ($report['classes'] as $class) {
                $this->info('Class '.$class['lab_session_id'].' / '.$class['class_code'].' / '.$class['subject_name']);
                $this->scope($class);
            }
            $this->info('Research collection status — overall');
            $this->scope($report['overall']);
            $this->line('Feature version: '.$report['feature_version']);
            $this->line('Official passing criterion: '.$report['passing_criterion_status'].($report['passing_criterion'] ? ' / '.$report['passing_criterion']['passing_operator'].' '.$report['passing_criterion']['passing_grade'] : ''));
            $this->line('Target generation: '.$report['target_generation']);
            $this->line('ML training status: '.$report['supervised_training_status']);
            $this->line('Populated zero-valued counts/totals are not proof of usage. Verified measurements are reported separately; no universal sufficiency threshold is applied.');
            $this->line('Feature/outcome matches here describe current availability, not a temporally validated training join. No risk, targets, imputation or training are calculated.');
            if ($path) {
                $this->info('Saved '.$path);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function scope(array $scope): void
    {
        $this->table(['Observation', 'Count'], collect($scope['metrics'])->map(fn ($value, $key) => [$key, $value])->values()->all());
        $this->table(['Frozen predictor / evidence', 'Available rows', 'Missing rows', 'Coverage %'], collect($scope['coverage'])
            ->map(fn ($value, $key) => [$key, $value['available_rows'], $value['missing_rows'], $value['coverage_percent'] ?? 'Not applicable'])->values()->all());
        $this->table(['Area', 'Status', 'Collection note'], $scope['flags']);
    }
}
