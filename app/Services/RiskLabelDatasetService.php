<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RiskLabelDatasetService
{
    public function latestFeaturePath(): string
    {
        $paths = glob(storage_path('app/risk-datasets/*-ml-ready.csv')) ?: [];
        rsort($paths);
        usort($paths, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return $paths[0] ?? throw new InvalidArgumentException('No ML-ready feature CSV found; provide an explicit feature CSV path.');
    }

    private function pair(array $row): string
    {
        return $row['student_group_key'].':'.$row['lab_session_id'];
    }

    public function inspect(string $path, ?array $classIds = null, ?CarbonInterface $asOf = null, bool $computeTargetDistribution = true): array
    {
        $resolved = realpath($path);
        if ($resolved === false || ! is_file($resolved) || ! is_readable($resolved)) {
            throw new InvalidArgumentException('Input must be a readable local ML-ready CSV.');
        }
        $inputHash = hash_file('sha256', $resolved);
        // Existing safe reader validates local files, numeric values, identifiers and structured topics.
        $readiness = app(RiskDatasetReportService::class)->analyze($path);
        if ($readiness['identity_column'] !== 'student_group_key') {
            throw new InvalidArgumentException('A research-safe ML-ready CSV with student_group_key is required.');
        }
        $stream = fopen(realpath($path), 'rb');
        try {
            $header = fgetcsv($stream, escape: '');
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $allowed = app(RiskFeatureSchema::class)->columns(true, true);
            if (array_diff($header, $allowed)) {
                throw new InvalidArgumentException('Feature CSV contains unapproved columns; provide an unlabeled ML-ready predictor export.');
            }
            $required = [...app(RiskFeatureSchema::class)->core(), 'student_group_key', 'lab_session_id', 'feature_cutoff_at', 'feature_version'];
            if (array_diff($required, $header)) {
                throw new InvalidArgumentException('Feature CSV is missing required core/grouping metadata columns.');
            }
            $rows = [];
            $versions = [];
            $cutoffs = [];
            while (($values = fgetcsv($stream, escape: '')) !== false) {
                if ($values === [null]) {
                    continue;
                }
                $row = array_combine($header, $values);
                $cutoff = $this->date($row['feature_cutoff_at']);
                if (! $cutoff || trim($row['feature_version']) === '') {
                    throw new InvalidArgumentException('Every feature row requires a valid cutoff and feature version.');
                }
                $versions[$row['feature_version']] = true;
                $cutoffs[$row['feature_cutoff_at']] = true;
                $rows[] = $row;
            }
        } finally {
            fclose($stream);
        }
        if ($inputHash !== hash_file('sha256', $resolved)) {
            throw new InvalidArgumentException('Feature CSV changed during inspection; retry with a stable export.');
        }

        return DB::transaction(function () use ($rows, $header, $versions, $cutoffs, $readiness, $inputHash, $classIds, $asOf, $computeTargetDistribution) {
            $outcomes = app(OfficialOutcomeResearchService::class)->rows($classIds, $asOf);
            $featureGroups = collect($rows)->groupBy(fn ($row) => $this->pair($row));
            $outcomeGroups = $outcomes->groupBy(fn ($row) => $this->pair($row));
            $duplicateOutcomes = $outcomeGroups->filter(fn ($group) => $group->count() > 1)->count();
            $duplicateFinalRecords = DB::table('subject_grades')->whereRaw("LOWER(TRIM(grading_period)) = 'final'")
                ->when($classIds !== null, fn ($q) => $q->whereIn('lab_session_id', $classIds))
                ->when($asOf !== null, fn ($q) => $q->where('created_at', '<=', $asOf))
                ->select('user_id', 'lab_session_id')->groupBy('user_id', 'lab_session_id')->havingRaw('COUNT(*) > 1')->get()->count();
            $classes = [];
            $matches = [];
            $timing = ['outcome_before_cutoff' => 0, 'outcome_at_cutoff' => 0, 'missing_outcome_timestamp' => 0, 'future_outcome_timestamp' => 0];
            $criterionError = null;
            try {
                $criterion = app(RiskTargetService::class)->criterion();
            } catch (InvalidArgumentException $e) {
                $criterion = null;
                $criterionError = $e->getMessage();
            }
            $balance = ['at_risk' => 0, 'not_at_risk' => 0];
            foreach (collect($rows)->pluck('lab_session_id')->merge($outcomes->pluck('lab_session_id'))->unique() as $classId) {
                $classRows = collect($rows)->where('lab_session_id', $classId);
                $classOutcomes = $outcomes->where('lab_session_id', $classId);
                $classes[$classId] = ['lab_session_id' => $classId, 'feature_rows' => $classRows->count(), 'outcomes' => $classOutcomes->count(),
                    'matched_rows' => 0, 'match_coverage_percent' => null, 'at_risk' => $criterion && $computeTargetDistribution ? 0 : null, 'not_at_risk' => $criterion && $computeTargetDistribution ? 0 : null];
            }
            foreach ($outcomes as $outcome) {
                if ($criterion && $computeTargetDistribution) {
                    $label = app(RiskTargetService::class)->label($outcome['final_outcome']);
                    $balance[$label]++;
                    $classes[$outcome['lab_session_id']][$label]++;
                }
            }
            foreach ($rows as $row) {
                $candidates = $outcomeGroups->get($this->pair($row));
                if (! $candidates || $candidates->count() !== 1) {
                    continue;
                }
                $outcome = $candidates->first();
                $classes[$row['lab_session_id']]['matched_rows']++;
                $recorded = $this->date($outcome['recorded_at']);
                $cutoff = $this->date($row['feature_cutoff_at']);
                if (! $recorded) {
                    $timing['missing_outcome_timestamp']++;
                } elseif ($recorded->lt($cutoff)) {
                    $timing['outcome_before_cutoff']++;
                } elseif ($recorded->eq($cutoff)) {
                    $timing['outcome_at_cutoff']++;
                }
                if ($recorded && ($asOf ? $recorded->gt($asOf) : $recorded->isFuture())) {
                    $timing['future_outcome_timestamp']++;
                }
                $matches[] = ['feature' => $row, 'outcome' => $outcome];
            }
            foreach ($classes as &$class) {
                $class['match_coverage_percent'] = $class['feature_rows'] ? round(100 * $class['matched_rows'] / $class['feature_rows'], 2) : null;
                foreach (['at_risk', 'not_at_risk'] as $label) {
                    $class[$label.'_percent'] = $criterion && $computeTargetDistribution && $class['outcomes'] ? round(100 * $class[$label] / $class['outcomes'], 2) : null;
                }
            }
            unset($class);
            $summary = ['feature_rows' => count($rows), 'feature_students' => $readiness['unique_students'], 'feature_classes' => $readiness['unique_classes'],
                'invalid_feature_cells' => array_sum(array_column($readiness['features'], 'invalid_numeric')),
                'feature_versions' => array_keys($versions), 'feature_cutoffs' => array_keys($cutoffs),
                'official_outcomes' => $outcomes->count(), 'outcome_students' => $outcomes->pluck('student_group_key')->unique()->count(),
                'outcome_classes' => $outcomes->pluck('lab_session_id')->unique()->count(),
                'imported_revision_count' => DB::table('subject_grade_revisions as r')->join('subject_grades as g', 'g.id', '=', 'r.subject_grade_id')->whereNotNull('r.import_id')
                    ->when($classIds !== null, fn ($q) => $q->whereIn('g.lab_session_id', $classIds))
                    ->when($asOf !== null, fn ($q) => $q->where('r.created_at', '<=', $asOf)
                        ->where(fn ($q) => $q->whereNull('r.recorded_at')->orWhere('r.recorded_at', '<=', $asOf)))->count(),
                'matched_rows' => count($matches), 'feature_rows_without_outcomes' => collect($rows)->filter(fn ($row) => ! $outcomeGroups->has($this->pair($row)))->count(),
                'outcomes_without_features' => $outcomes->filter(fn ($row) => ! $featureGroups->has($this->pair($row)))->count(),
                'duplicate_feature_pairs' => $featureGroups->filter(fn ($group) => $group->count() > 1)->count(),
                'duplicate_feature_rows' => $readiness['duplicate_pair_rows'], 'duplicate_outcome_pairs' => $duplicateOutcomes,
                'duplicate_final_record_pairs' => $duplicateFinalRecords,
                'ambiguous_feature_rows' => collect($rows)->filter(fn ($row) => ($outcomeGroups->get($this->pair($row))?->count() ?? 0) > 1)->count(),
                ...$timing, 'criterion' => $criterion, 'criterion_error' => $criterionError,
                'target_balance' => $criterion && $computeTargetDistribution ? [...$balance, 'at_risk_percent' => $outcomes->isEmpty() ? null : round(100 * $balance['at_risk'] / $outcomes->count(), 2),
                    'not_at_risk_percent' => $outcomes->isEmpty() ? null : round(100 * $balance['not_at_risk'] / $outcomes->count(), 2)] : null,
                'classes' => array_values($classes)];

            return ['summary' => $summary, 'header' => $header, 'matches' => $matches, 'input_sha256' => $inputHash];
        });
    }

    private function date(?string $value): ?Carbon
    {
        if (! $value || ! Carbon::canBeCreatedFromFormat($value, 'Y-m-d H:i:s')) {
            return null;
        }
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $value, config('app.timezone'));

        return $date->format('Y-m-d H:i:s') === $value ? $date : null;
    }

    public function export(string $input, string $filename): array
    {
        $target = app(RiskTargetService::class);
        $criterion = $target->requireCriterion();
        $report = $this->inspect($input);
        $summary = $report['summary'];
        if ($summary['invalid_feature_cells']) {
            throw new InvalidArgumentException('Invalid numeric predictor cells require review before labeled export.');
        }
        if ($summary['duplicate_feature_pairs'] || $summary['duplicate_outcome_pairs'] || $summary['duplicate_final_record_pairs']) {
            throw new InvalidArgumentException('Duplicate student/class keys must be resolved before labeled export.');
        }
        if ($summary['outcome_before_cutoff'] || $summary['outcome_at_cutoff'] || $summary['missing_outcome_timestamp'] || $summary['future_outcome_timestamp']) {
            throw new InvalidArgumentException('Unsafe or missing outcome recording timestamps: inspect risk:label-readiness before labeled export.');
        }
        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.csv\z/', $filename)) {
            throw new InvalidArgumentException('--output must be a CSV filename without directories.');
        }
        $path = storage_path('app/risk-datasets/'.$filename);
        $manifestPath = substr($path, 0, -4).'.schema.json';
        if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true)) {
            throw new RuntimeException('Cannot create output directory.');
        }
        $created = [];
        $stream = null;
        $manifest = null;
        try {
            $stream = @fopen($path, 'x');
            if (! $stream) {
                throw new RuntimeException('Choose a new labeled CSV filename; existing files are never overwritten.');
            } $created[] = $path;
            $manifest = @fopen($manifestPath, 'x');
            if (! $manifest) {
                throw new RuntimeException('Choose a new schema filename; existing files are never overwritten.');
            } $created[] = $manifestPath;
            $header = [...$report['header'], config('risk_target.ground_truth_column'), config('risk_target.target_name')];
            $write = function ($row) use ($stream) {
                if (fputcsv($stream, $row, ',', '"', '') === false) {
                    throw new RuntimeException('Cannot write labeled CSV.');
                }
            };
            $write($header);
            foreach ($report['matches'] as $match) {
                $write([...array_values($match['feature']), $match['outcome']['final_outcome'], $target->label($match['outcome']['final_outcome'])]);
            }
            $payload = ['columns' => $header, 'categories' => $target->categories(), 'criterion' => $criterion,
                'target_values' => ['at_risk' => 'At-Risk', 'not_at_risk' => 'Not At-Risk'],
                'feature_versions' => $summary['feature_versions'], 'feature_cutoffs' => $summary['feature_cutoffs'],
                'selected_outcome_revision_ids' => array_map(fn ($match) => $match['outcome']['outcome_revision_id'], $report['matches']),
                'input_sha256' => $report['input_sha256'], 'imputation' => 'none', 'topic_encoding' => 'pending'];
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            if (fwrite($manifest, $json) !== strlen($json) || ! fflush($stream) || ! fflush($manifest)) {
                throw new RuntimeException('Cannot flush labeled dataset/schema.');
            }
        } catch (\Throwable $e) {
            foreach ($created as $file) {
                if (is_resource($stream)) {
                    fclose($stream);
                    $stream = null;
                } if (is_resource($manifest)) {
                    fclose($manifest);
                    $manifest = null;
                } unlink($file);
            } throw $e;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            } if (is_resource($manifest)) {
                fclose($manifest);
            }
        }

        return ['path' => $path, 'schema_path' => $manifestPath, 'row_count' => count($report['matches'])];
    }
}
