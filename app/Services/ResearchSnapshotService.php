<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Process;

class ResearchSnapshotService
{
    public function create(string $name, ?array $classIds, CarbonInterface $point, bool $saveFeatures = false): array
    {
        $name = $this->safeName($name);
        $cutoff = Carbon::instance($point)->startOfSecond();
        $root = storage_path('app/risk-datasets/research-snapshots');
        if (! is_dir($root) && ! @mkdir($root, 0755, true) && ! is_dir($root)) {
            throw new RuntimeException('Cannot create research snapshot root.');
        }
        $directory = $root.'/'.$name.'-'.$cutoff->format('Ymd-His');
        // Exclusive creation also handles concurrent commands with the same name.
        if (! @mkdir($directory, 0755)) {
            throw new RuntimeException('Snapshot directory already exists or cannot be created; nothing was overwritten.');
        }
        $created = [];
        try {
            // The production MySQL connection must retain one consistent read view
            // across the export and reports, even while collection continues.
            if (DB::connection()->getDriverName() === 'mysql' && DB::transactionLevel() === 0) {
                DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            }

            $metadata = DB::transaction(function () use ($name, $classIds, $cutoff, $saveFeatures, $directory, &$created) {
                $summary = $this->write($directory.'/ml-ready.csv', $created, fn ($stream) => app(StudentRiskDatasetService::class)->exportClassesCsv($classIds, $cutoff, $stream, $saveFeatures, true));
                $ids = array_column($summary['classes'], 'lab_session_id');
                // The audit accepts null (automatic selection), not an empty list.
                // With no selected classes the same read view selects none again.
                $collection = app(RiskCollectionStatusService::class)->report($ids ?: null, $cutoff, true);
                $this->write($directory.'/collection-status.csv', $created, fn ($stream) => app(RiskCollectionStatusService::class)->writeCsv($collection, $stream));
                $this->writeJson($directory.'/collection-status.json', $created, $collection);
                $readiness = app(RiskLabelDatasetService::class)->inspect(
                    $directory.'/ml-ready.csv', $ids, $cutoff, computeTargetDistribution: false)['summary'];
                if ($summary['row_count'] !== $readiness['feature_rows']
                    || $summary['row_count'] !== $collection['overall']['metrics']['student_class_rows']) {
                    throw new RuntimeException('Snapshot scope counts disagree; no completed package was created.');
                }
                $text = $this->readinessText($readiness, $collection, $cutoff);
                $this->write($directory.'/label-readiness.txt', $created, fn ($stream) => $this->bytes($stream, $text));

                $scope = ['groups' => app(RiskFeatureSchema::class)->groups(),
                    'columns' => app(RiskFeatureSchema::class)->columns(true, true),
                    'excluded' => config('risk_features.excluded')];
                $checksums = [];
                foreach ($created as $file) {
                    $checksums[basename($file)] = hash_file('sha256', $file);
                }
                $metadata = [
                    'snapshot_name' => $name,
                    'generated_at' => $cutoff->toIso8601String(),
                    'feature_cutoff_at' => $cutoff->format('Y-m-d H:i:s'),
                    'timezone' => config('app.timezone'),
                    'feature_version' => StudentRiskFeatureService::FEATURE_VERSION,
                    'selected_class_ids' => $ids,
                    'row_count' => $summary['row_count'],
                    'unique_student_count' => $readiness['feature_students'],
                    'class_count' => count($ids),
                    'predictor_scope_sha256' => hash('sha256', json_encode($scope, JSON_THROW_ON_ERROR)),
                    'research_code_sha256' => $this->codeHash(),
                    'target_config_status' => $collection['passing_criterion_status'],
                    'passing_criterion_configured' => $collection['passing_criterion'] !== null,
                    'passing_criterion' => $collection['passing_criterion'],
                    'official_outcome_count' => $readiness['official_outcomes'],
                    'official_outcomes_with_valid_timestamp' => $collection['overall']['metrics']['official_outcomes'],
                    'target_generation' => 'DISABLED: research snapshots never generate labels',
                    'features_saved' => $saveFeatures,
                    'git_commit' => $this->git(['rev-parse', 'HEAD']),
                    'git_worktree_dirty' => ($status = $this->git(['status', '--porcelain'])) === null ? null : $status !== '',
                    'filenames_created' => [...array_keys($checksums), 'metadata.json'],
                    'artifact_sha256' => $checksums,
                ];
                // Written last: metadata marks a completed package. The metadata
                // file deliberately does not attempt to checksum itself.
                $this->writeJson($directory.'/metadata.json', $created, $metadata);

                return $metadata;
            });

            return ['directory' => $directory, 'metadata' => $metadata];
        } catch (\Throwable $e) {
            // Only files exclusively created by this invocation are removed.
            // No recursive deletion and no cleanup of pre-existing directories.
            foreach (array_reverse($created) as $file) {
                @unlink($file);
            }
            @rmdir($directory);
            throw $e;
        }
    }

    private function safeName(string $name): string
    {
        if (preg_match('/[\x00-\x1F\x7F\/\\\\]/', $name) || str_contains($name, '..')) {
            throw new InvalidArgumentException('Snapshot name must not contain paths, traversal or control characters.');
        }
        $slug = Str::slug(trim($name));
        if ($slug === '' || strlen($slug) > 80) {
            throw new InvalidArgumentException('Provide a snapshot name that sanitizes to 1–80 characters.');
        }

        return $slug;
    }

    private function write(string $path, array &$created, callable $writer): mixed
    {
        $stream = @fopen($path, 'x');
        if (! $stream) {
            throw new RuntimeException('Cannot exclusively create snapshot artifact.');
        }
        $created[] = $path;
        try {
            $result = $writer($stream);
            if (! fflush($stream)) {
                throw new RuntimeException('Cannot flush snapshot artifact.');
            }

            return $result;
        } finally {
            fclose($stream);
        }
    }

    private function bytes($stream, string $text): void
    {
        if (fwrite($stream, $text) !== strlen($text)) {
            throw new RuntimeException('Cannot write complete snapshot artifact.');
        }
    }

    private function writeJson(string $path, array &$created, array $value): void
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
        $this->write($path, $created, fn ($stream) => $this->bytes($stream, $json));
    }

    private function readinessText(array $report, array $collection, CarbonInterface $cutoff): string
    {
        $lines = ['LMMS-CCS research snapshot: label readiness',
            'Feature dataset: ml-ready.csv',
            'Feature cutoff: '.$cutoff->format('Y-m-d H:i:s').' ('.config('app.timezone').')',
            'Feature version: '.StudentRiskFeatureService::FEATURE_VERSION,
            'Passing criterion: '.$collection['passing_criterion_status'],
            'Target generation: DISABLED; no labels or target distributions computed.',
            'Training: not performed.', ''];
        foreach ($report as $key => $value) {
            if (in_array($key, ['classes', 'criterion', 'criterion_error', 'target_balance'], true)) {
                continue;
            }
            $lines[] = $key.': '.(is_array($value) ? implode(', ', $value) : ($value ?? 'Unavailable'));
        }
        $lines[] = '';
        $lines[] = 'Class | Feature rows | Official outcomes | Matched rows | Match coverage %';
        foreach ($report['classes'] as $class) {
            $lines[] = implode(' | ', [$class['lab_session_id'], $class['feature_rows'], $class['outcomes'], $class['matched_rows'], $class['match_coverage_percent'] ?? 'Unavailable']);
        }
        $lines[] = '';
        $lines[] = 'Outcome revisions are restricted to selected classes and records known by the shared cutoff.';
        $lines[] = 'recorded_at is database recording time, not the academic event date; official provenance requires separate review.';
        $lines[] = 'Duplicate keys, missing timestamps and outcomes recorded at/before observation require review before any future labeled export.';
        $lines[] = 'No imputation, target labels, outcome rules or models are created by this command.';

        return implode("\n", $lines)."\n";
    }

    private function git(array $arguments): ?string
    {
        try {
            $process = new Process(['git', ...$arguments], base_path(), timeout: 10);
            $process->run();

            return $process->isSuccessful() ? trim($process->getOutput()) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function codeHash(): string
    {
        // Covers the current uncommitted risk implementation as well as the
        // committed baseline. Only hashes are exported; no secrets or source text.
        $files = [...glob(app_path('Services/*Risk*.php')), ...glob(app_path('Services/ResearchSnapshot*.php')),
            app_path('Services/OfficialOutcomeResearchService.php'), ...glob(app_path('Console/Commands/*Risk*.php')),
            app_path('Console/Commands/CreateResearchSnapshot.php'), config_path('risk_features.php'), config_path('risk_target.php')];
        sort($files);
        $hashes = array_map(fn ($file) => [basename($file), hash_file('sha256', $file)], $files);

        return hash('sha256', json_encode($hashes, JSON_THROW_ON_ERROR));
    }
}
