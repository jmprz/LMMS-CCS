<?php

namespace App\Console\Commands;

use App\Services\StudentRiskDatasetService;
use App\Services\StudentRiskFeatureService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

class ExportClassRiskDataset extends Command
{
    protected $signature = 'risk:export-class
                            {lab_session_id : Lab session ID}
                            {--cutoff= : Required prediction cutoff in application timezone, YYYY-MM-DD HH:MM:SS}
                            {--output= : CSV path; defaults to storage/app/risk-datasets}
                            {--save : Also persist class feature snapshots}
                            {--ml-ready : Export finalized ML scope with pseudonymous student grouping keys}';

    protected $description = 'Export class risk predictors with a shared cutoff and optional bulk snapshots';

    public function handle(StudentRiskDatasetService $datasets): int
    {
        $stream = null;
        $path = null;
        try {
            $value = $this->option('cutoff');
            if (! $value || ! Carbon::canBeCreatedFromFormat($value, 'Y-m-d H:i:s')) {
                throw new InvalidArgumentException('--cutoff is required in YYYY-MM-DD HH:MM:SS format.');
            }
            $cutoff = Carbon::createFromFormat('Y-m-d H:i:s', $value, config('app.timezone'))->startOfSecond();
            if ($cutoff->format('Y-m-d H:i:s') !== $value || $cutoff->isFuture()) {
                throw new InvalidArgumentException('Cutoff must be a valid prediction point at or before now.');
            }
            $classId = filter_var($this->argument('lab_session_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($classId === false) {
                throw new InvalidArgumentException('Lab session ID must be a positive integer.');
            }
            $path = $this->option('output') ?: storage_path(
                'app/risk-datasets/class-'.$classId.'-'.$cutoff->format('Ymd-His').'-'.StudentRiskFeatureService::FEATURE_VERSION.($this->option('ml-ready') ? '-ml-ready' : '').'.csv'
            );
            if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true) && ! is_dir(dirname($path))) {
                throw new RuntimeException('Could not create CSV output directory.');
            }
            // Exclusive creation prevents accidentally replacing an existing research dataset.
            $stream = @fopen($path, 'x');
            if ($stream === false) {
                throw new RuntimeException("Cannot create {$path}; choose a new path if the file already exists.");
            }
            $count = $datasets->exportCsv($classId, $cutoff, $stream, (bool) $this->option('save'), (bool) $this->option('ml-ready'));
            fclose($stream);
            $stream = null;
            $this->info("Exported {$count} students to {$path}");
            $this->line('Shared cutoff: '.$cutoff->format('Y-m-d H:i:s').' ('.config('app.timezone').')');
            if ($this->option('save')) {
                $this->line('Saved or reused '.StudentRiskFeatureService::FEATURE_VERSION.' snapshots for the same student/class/cutoff.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (is_resource($stream)) {
                fclose($stream);
                // Only remove the incomplete file exclusively created by this invocation.
                @unlink($path);
            }
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
