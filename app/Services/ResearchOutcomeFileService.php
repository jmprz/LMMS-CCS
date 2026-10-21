<?php

namespace App\Services;

use InvalidArgumentException;

/** Local, file-only evidence ingress. No database, imports, exports or label persistence. */
class ResearchOutcomeFileService
{
    public function inspect(string $featurePath, string $evidencePath): array
    {
        foreach ([$featurePath, $evidencePath] as $path) {
            if (str_contains($path, '://') || ! is_file($path) || ! is_readable($path) || filesize($path) > 16777216) {
                throw new InvalidArgumentException('Use readable local research files of at most 16 MB each.');
            }
        }
        $featureHash = hash_file('sha256', $featurePath);
        $evidenceHash = hash_file('sha256', $evidencePath);
        $bundle = json_decode(file_get_contents($evidencePath), true, 64, JSON_THROW_ON_ERROR);
        if (! is_array($bundle) || ($bundle['feature_sha256'] ?? null) !== $featureHash) {
            throw new InvalidArgumentException('Evidence must bind the exact frozen feature file SHA-256.');
        }
        $synthetic = ($bundle['data_origin'] ?? null) === 'synthetic';
        if ($synthetic && ($bundle['simulation']['feature_sha256'] ?? null) !== $featureHash) {
            throw new InvalidArgumentException('Synthetic source hash mismatch.');
        }
        $schema = app(RiskFeatureSchema::class);
        $allowed = $schema->columns(true, true);
        if ($synthetic) {
            $allowed = [...$allowed, 'data_origin', 'synthetic_scenario', 'synthetic_run_id', 'synthetic_profile'];
        }
        $required = [...$schema->core(), 'student_group_key', 'lab_session_id', 'feature_cutoff_at', 'feature_version'];
        $stream = fopen($featurePath, 'rb');
        $features = [];
        try {
            $header = fgetcsv($stream, escape: '');
            if (! is_array($header)) {
                throw new InvalidArgumentException('Empty feature CSV.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            if (count($header) !== count(array_unique($header)) || array_diff($header, $allowed) || array_diff($required, $header)) {
                throw new InvalidArgumentException('Use only an unlabeled frozen-v5 allowlisted feature CSV.');
            }
            $counts = ['quiz_attempt_count', 'late_submission_count', 'materials_accessed', 'material_session_count', 'activity_count'];
            while (($cells = fgetcsv($stream, escape: '')) !== false) {
                if ($cells === [null]) {
                    continue;
                }
                if (count($cells) !== count($header)) {
                    throw new InvalidArgumentException('Malformed feature CSV row.');
                }
                $row = array_combine($header, $cells);
                foreach ([...$schema->core(), ...$schema->optional()] as $name) {
                    if (! array_key_exists($name, $row) || $row[$name] === '') {
                        continue;
                    }
                    if (! is_numeric($row[$name]) || ! is_finite((float) $row[$name]) || $row[$name] < 0 ||
                        (in_array($name, ['quiz_avg', 'task_avg', 'submission_rate', 'attendance_rate'], true) && $row[$name] > 100) ||
                        (in_array($name, $counts, true) && fmod((float) $row[$name], 1) != 0)) {
                        throw new InvalidArgumentException('Invalid numeric feature cell.');
                    }
                }
                if ($synthetic && ($row['data_origin'] ?? null) !== 'synthetic') {
                    throw new InvalidArgumentException('Synthetic feature provenance missing.');
                }
                $features[] = array_intersect_key($row, array_flip(['student_group_key', 'lab_session_id', 'feature_cutoff_at', 'feature_version']));
            }
        } finally {
            fclose($stream);
        }
        $report = app(ResearchOutcomePolicyService::class)->inspect($features, $bundle);
        if (hash_file('sha256', $featurePath) !== $featureHash || hash_file('sha256', $evidencePath) !== $evidenceHash) {
            throw new InvalidArgumentException('Research input changed during inspection.');
        }

        return [...$report, 'feature_sha256' => $featureHash, 'evidence_sha256' => $evidenceHash];
    }
}
