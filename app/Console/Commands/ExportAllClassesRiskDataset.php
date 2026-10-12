<?php

namespace App\Console\Commands;

use App\Services\StudentRiskDatasetService;
use App\Services\StudentRiskFeatureService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

class ExportAllClassesRiskDataset extends Command
{
    protected $signature = 'risk:export-all-classes
                            {--classes= : Optional comma-separated lab session IDs; otherwise active enrolled classes}
                            {--cutoff= : Required prediction cutoff in application timezone, YYYY-MM-DD HH:MM:SS}
                            {--output= : Optional CSV filename within storage/app/risk-datasets}
                            {--save : Also persist student/class feature snapshots}
                            {--ml-ready : Export finalized ML scope with pseudonymous student grouping keys}';

    protected $description = 'Export multiple classes into one risk predictor CSV using one shared cutoff';

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
            $classIds = null;
            if ($this->option('classes') !== null) {
                $classIds = [];
                foreach (explode(',', $this->option('classes')) as $id) {
                    $parsed = filter_var(trim($id), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if ($parsed === false) {
                        throw new InvalidArgumentException('--classes must contain comma-separated positive integer IDs.');
                    }
                    $classIds[] = $parsed;
                }
                $classIds = array_values(array_unique($classIds));
                sort($classIds);
            }
            $filename = $this->option('output') ?? (
                ($classIds === null ? 'all-classes' : 'classes-'.implode('-', $classIds))
                .'-'.$cutoff->format('Ymd-His').'-'.StudentRiskFeatureService::FEATURE_VERSION.($this->option('ml-ready') ? '-ml-ready' : '').'.csv'
            );
            if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.csv\z/i', $filename)) {
                throw new InvalidArgumentException('--output must be a CSV filename, without directories.');
            }
            $path = storage_path('app/risk-datasets/'.$filename);
            if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true) && ! is_dir(dirname($path))) {
                throw new RuntimeException('Could not create CSV output directory.');
            }
            $stream = @fopen($path, 'x');
            if ($stream === false) {
                throw new RuntimeException("Cannot create {$path}; choose a new filename if the file already exists.");
            }
            $summary = $datasets->exportClassesCsv($classIds, $cutoff, $stream, (bool) $this->option('save'), (bool) $this->option('ml-ready'));
            fclose($stream);
            $stream = null;
            $this->table(['Lab session', 'Class code', 'Subject', 'Rows'], $summary['classes']);
            $this->info('Exported '.$summary['row_count'].' rows across '.count($summary['classes'])." classes to {$path}");
            $this->line('Shared cutoff: '.$cutoff->format('Y-m-d H:i:s').' ('.config('app.timezone').')');
            if ($this->option('save')) {
                $this->line('Saved or reused '.StudentRiskFeatureService::FEATURE_VERSION.' snapshots for the same student/class/cutoff.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (is_resource($stream)) {
                fclose($stream);
                @unlink($path);
            }
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
