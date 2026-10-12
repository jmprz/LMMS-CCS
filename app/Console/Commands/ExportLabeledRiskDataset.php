<?php

namespace App\Console\Commands;

use App\Services\RiskLabelDatasetService;
use Illuminate\Console\Command;

class ExportLabeledRiskDataset extends Command
{
    protected $signature = 'risk:export-labeled-dataset {feature-csv : Unlabeled ML-ready feature CSV} {--output= : Filename under storage/app/risk-datasets}';

    protected $description = 'Join official outcomes to predictors and export labels only with an explicitly configured institutional criterion';

    public function handle(RiskLabelDatasetService $service): int
    {
        try {
            $result = $service->export($this->argument('feature-csv'), $this->option('output') ?? 'labeled-risk-dataset-'.now()->format('Ymd-His').'.csv');
            $this->info('Exported '.$result['row_count'].' matched rows to '.$result['path']);
            $this->info('Column categories and provenance: '.$result['schema_path']);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
