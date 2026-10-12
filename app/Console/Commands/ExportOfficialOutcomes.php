<?php

namespace App\Console\Commands;

use App\Services\OfficialOutcomeResearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ExportOfficialOutcomes extends Command
{
    protected $signature = 'risk:export-outcomes {--classes= : Optional comma-separated class IDs} {--output= : CSV filename under storage/app/risk-datasets}';

    protected $description = 'Export recorded official outcomes separately from predictors, without identities or risk labels';

    public function handle(OfficialOutcomeResearchService $outcomes): int
    {
        $stream = null;
        $path = null;
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
                $ids = array_unique($ids);
                if (DB::table('lab_sessions')->whereIn('id', $ids)->count() !== count($ids)) {
                    throw new InvalidArgumentException('Selected class does not exist.');
                }
            }
            $filename = $this->option('output') ?? 'official-outcomes-'.now()->format('Ymd-His').'.csv';
            if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.csv\z/', $filename)) {
                throw new InvalidArgumentException('--output must be a CSV filename without directories.');
            }
            $path = storage_path('app/risk-datasets/'.$filename);
            if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true)) {
                throw new RuntimeException('Cannot create output directory.');
            }
            $stream = fopen($path, 'x');
            if (! $stream) {
                throw new RuntimeException('Choose a new filename; existing files are never overwritten.');
            }
            $rows = $outcomes->rows($ids);
            $write = function ($row) use ($stream) {
                if (fputcsv($stream, $row, ',', '"', '') === false) {
                    throw new RuntimeException('Cannot write outcome CSV.');
                }
            };
            $write(['student_group_key', 'lab_session_id', 'final_outcome', 'recorded_at', 'outcome_revision_id']);
            foreach ($rows as $row) {
                $write(array_values($row));
            }
            if (! fflush($stream)) {
                throw new RuntimeException('Cannot flush outcome CSV.');
            }
            fclose($stream);
            $stream = null;
            $this->info('Exported '.$rows->count().' official student-class outcomes to '.$path);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (is_resource($stream)) {
                fclose($stream);
                if ($path) {
                    unlink($path);
                }
            }
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
