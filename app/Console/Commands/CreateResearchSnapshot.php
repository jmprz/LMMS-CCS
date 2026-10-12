<?php

namespace App\Console\Commands;

use App\Services\ResearchSnapshotService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CreateResearchSnapshot extends Command
{
    protected $signature = 'risk:research-snapshot
        {--name= : Snapshot name (required; sanitized to a safe slug)}
        {--classes= : Comma-separated class IDs; defaults to active enrolled classes}
        {--save-features : Explicitly persist v5 feature snapshots; default is read-only}';

    protected $description = 'Package an immutable unlabeled research snapshot using one current cutoff';

    public function handle(ResearchSnapshotService $service): int
    {
        $cutoff = now()->startOfSecond();
        try {
            $classes = null;
            if ($this->option('classes') !== null) {
                $tokens = array_map('trim', explode(',', $this->option('classes')));
                foreach ($tokens as $token) {
                    if (! preg_match('/\A[1-9][0-9]*\z/', $token) || filter_var($token, FILTER_VALIDATE_INT) === false) {
                        throw new InvalidArgumentException('--classes must contain positive integer class IDs.');
                    }
                }
                $classes = array_values(array_unique(array_map('intval', $tokens)));
            }
            $result = $service->create((string) $this->option('name'), $classes, $cutoff, (bool) $this->option('save-features'));
            $meta = $result['metadata'];
            $this->info('Research snapshot: '.$result['directory']);
            $this->table(['Measure', 'Value'], [
                ['Feature version', $meta['feature_version']], ['Shared cutoff', $meta['feature_cutoff_at'].' '.$meta['timezone']],
                ['Student-class rows', $meta['row_count']], ['Unique students', $meta['unique_student_count']],
                ['Classes', $meta['class_count']], ['Official outcomes', $meta['official_outcome_count']],
                ['Passing criterion', $meta['target_config_status']],
                ['Feature persistence', $meta['features_saved'] ? 'Explicitly requested' : 'None (read-only)'],
            ]);
            $this->line('Files: '.implode(', ', $meta['filenames_created']));
            $this->line('No labels generated and no models trained. Existing packages are never overwritten.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Do not expose raw SQL exception messages (which may contain PII).
            $this->error($e instanceof InvalidArgumentException || $e instanceof \RuntimeException
                && ! $e instanceof \Illuminate\Database\QueryException ? $e->getMessage() : 'Snapshot failed; no completed package was created.');

            return self::FAILURE;
        }
    }
}
