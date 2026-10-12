<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class RiskDatasetReportService
{
    public function analyze(string $path): array
    {
        // Reject URLs, PHP stream wrappers and special files; only read local regular files.
        $resolved = realpath($path);
        if ($resolved === false || ! is_file($resolved) || ! is_readable($resolved)) {
            throw new InvalidArgumentException('Input must be a readable local CSV file.');
        }
        $stream = @fopen($resolved, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Could not open the input CSV.');
        }

        try {
            $header = fgetcsv($stream, escape: '');
            if ($header === false || $header === [null]) {
                throw new InvalidArgumentException('CSV must contain a header row.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '');
            $header = array_map(fn ($value) => trim((string) $value), $header);
            if (in_array('', $header, true) || count(array_unique($header)) !== count($header)) {
                throw new InvalidArgumentException('CSV column names must be non-empty and unique.');
            }
            $identityColumn = in_array('student_group_key', $header, true) ? 'student_group_key' : 'user_id';
            foreach ([$identityColumn, 'lab_session_id'] as $required) {
                if (! in_array($required, $header, true)) {
                    throw new InvalidArgumentException("CSV is missing required column {$required}.");
                }
            }
            $mapping = [];
            $absent = [];
            foreach (config('risk_features.aliases') as $feature => $aliases) {
                $found = array_values(array_intersect($header, $aliases));
                foreach ($found as $column) {
                    $mapping[$column] = $feature;
                }
                if ($found === [] && in_array($feature, app(RiskFeatureSchema::class)->core(), true)) {
                    $absent[] = $feature;
                }
            }
            foreach ($header as $column) {
                if (preg_match('/\Atopic_quiz_average__topic_[0-9]+\z/', $column)) {
                    $mapping[$column] = 'topic_quiz_average';
                }
            }
            if (! in_array('topic_quiz_average', $mapping, true) && ! in_array('topic_quiz_performance_json', $header, true)) {
                $absent[] = 'topic_quiz_average';
            }
            if ($mapping === []) {
                throw new InvalidArgumentException('CSV contains no recognized risk feature columns.');
            }

            $overall = $this->accumulators($mapping);
            $classes = [];
            $students = [];
            $pairs = [];
            $cutoffs = [];
            $rows = 0;
            $duplicates = 0;
            $record = 1;
            $topicCoverage = new RiskTopicCoverageService;
            while (($values = fgetcsv($stream, escape: '')) !== false) {
                $record++;
                if ($values === [null]) {
                    continue; // Ignore blank lines, not rows containing empty CSV cells.
                }
                if (count($values) !== count($header)) {
                    throw new InvalidArgumentException("CSV record {$record} has the wrong number of cells.");
                }
                $row = array_combine($header, $values);
                $user = trim((string) $row[$identityColumn]);
                $class = trim((string) $row['lab_session_id']);
                $validStudent = preg_match($identityColumn === 'student_group_key' ? '/\A[a-f0-9]{64}\z/' : '/\A[1-9][0-9]*\z/', $user);
                if (! $validStudent || ! preg_match('/\A[1-9][0-9]*\z/', $class)) {
                    // Never echo the offending cell or any individual student identifier.
                    throw new InvalidArgumentException("CSV record {$record} has invalid student grouping or class identifiers.");
                }
                $rows++;
                $topicCoverage->observe($row);
                $students[$user] = true;
                $pair = $user.':'.$class;
                $duplicates += isset($pairs[$pair]) ? 1 : 0;
                $pairs[$pair] = true;
                if (isset($row['feature_cutoff_at']) && ! $this->missing($row['feature_cutoff_at'])) {
                    $cutoffs[trim($row['feature_cutoff_at'])] = true;
                }
                if (! isset($classes[$class])) {
                    $classes[$class] = ['lab_session_id' => $class, 'row_count' => 0, 'students' => [], 'features' => $this->accumulators($mapping)];
                }
                $classes[$class]['row_count']++;
                $classes[$class]['students'][$user] = true;
                foreach ($mapping as $column => $feature) {
                    $this->observe($overall[$column], $row[$column]);
                    $this->observe($classes[$class]['features'][$column], $row[$column]);
                }
            }
            if (! feof($stream)) {
                throw new RuntimeException('Could not finish reading the CSV.');
            }
            ksort($classes, SORT_NUMERIC);
            foreach ($classes as &$class) {
                $class['unique_students'] = count($class['students']);
                unset($class['students']);
                $class['features'] = $this->finalize($class['features'], $class['row_count']);
                $class['completeness_percent'] = $this->cellCompleteness($class['features'], $class['row_count']);
            }
            unset($class);

            $topicSummary = array_map(function ($entry) use ($classes, $rows) {
                $total = $entry['scope'] === 'overall' ? $rows : $classes[$entry['lab_session_id']]['row_count'];

                return [...$entry, 'total_rows' => $total, 'completeness_percent' => $total === 0 ? null
                    : round(100 * $entry['student_class_rows_with_score'] / $total, 2)];
            }, $topicCoverage->summary());

            return [
                'row_count' => $rows,
                'feature_groups' => app(RiskFeatureSchema::class)->groups(),
                'excluded_predictors' => config('risk_features.excluded'),
                'identity_column' => $identityColumn,
                'topic_coverage' => $topicSummary,
                'has_topic_json' => in_array('topic_quiz_performance_json', $header, true) || in_array('topic_task_performance_json', $header, true),
                'unique_students' => count($students),
                'unique_classes' => count($classes),
                'unique_student_class_pairs' => count($pairs),
                'duplicate_pair_rows' => $duplicates,
                'cutoff_count' => count($cutoffs),
                'features' => $this->finalize($overall, $rows),
                'absent_features' => $absent,
                'classes' => array_values($classes),
            ];
        } finally {
            fclose($stream);
        }
    }

    /** Export aggregate numeric summaries only; no individual IDs, names or raw rows. */
    public function export(array $report, string $filename): array
    {
        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.csv\z/i', $filename)) {
            throw new InvalidArgumentException('--output must be a CSV filename without directories.');
        }
        $directory = storage_path('app/risk-datasets');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create report output directory.');
        }
        $paths = [$directory.'/'.$filename, $directory.'/'.substr($filename, 0, -4).'-classes.csv'];
        if ($report['has_topic_json'] ?? false) {
            $paths[] = $directory.'/'.substr($filename, 0, -4).'-topics.csv';
        }
        $created = [];
        try {
            foreach ($paths as $i => $path) {
                $stream = @fopen($path, 'x');
                if ($stream === false) {
                    throw new RuntimeException('Cannot create report output; choose a new filename if it already exists.');
                }
                $created[] = $path;
                try {
                    if ($i === 2) {
                        $columns = ['scope', 'lab_session_id', 'kind', 'total_rows', 'student_class_rows_with_score', 'completeness_percent', 'unique_students_with_score', 'tagged_items', 'untagged_items'];
                        $this->write($stream, $columns);
                        foreach ($report['topic_coverage'] as $row) {
                            $this->write($stream, array_map(fn ($column) => $row[$column] ?? null, $columns));
                        }
                        if (! fflush($stream)) {
                            throw new RuntimeException('Could not flush topic coverage CSV.');
                        }

                        continue;
                    }
                    $columns = ['group', 'feature', 'source_column', 'total_rows', 'non_missing', 'missing', 'completeness_percent', 'missing_percent', 'numeric_count', 'invalid_numeric', 'min', 'max', 'mean', 'median', 'sample_stddev'];
                    if ($i === 1) {
                        array_unshift($columns, 'lab_session_id', 'class_rows', 'class_unique_students');
                    }
                    $this->write($stream, $columns);
                    $groups = $i === 0 ? [['features' => $report['features']]] : $report['classes'];
                    foreach ($groups as $group) {
                        foreach ($group['features'] as $feature) {
                            $row = $feature;
                            $row['group'] = app(RiskFeatureSchema::class)->group($feature['feature']);
                            if ($i === 1) {
                                $row['lab_session_id'] = $group['lab_session_id'];
                                $row['class_rows'] = $group['row_count'];
                                $row['class_unique_students'] = $group['unique_students'];
                            }
                            $this->write($stream, array_map(fn ($column) => $row[$column] ?? null, $columns));
                        }
                    }
                    if (! fflush($stream)) {
                        throw new RuntimeException('Could not flush report CSV.');
                    }
                } finally {
                    fclose($stream);
                }
            }
        } catch (\Throwable $e) {
            foreach ($created as $path) {
                @unlink($path);
            }
            throw $e;
        }

        return $paths;
    }

    private function missing(?string $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['', 'null', 'na', 'n/a', 'nan', 'none'], true);
    }

    private function accumulators(array $mapping): array
    {
        $features = [];
        foreach ($mapping as $column => $feature) {
            $features[$column] = ['feature' => $feature, 'source_column' => $column, 'non_missing' => 0, 'invalid_numeric' => 0, 'values' => [], 'mean_acc' => 0.0, 'm2' => 0.0];
        }

        return $features;
    }

    private function observe(array &$feature, ?string $value): void
    {
        if ($this->missing($value)) {
            return;
        }
        $feature['non_missing']++;
        if (! is_numeric(trim($value)) || ! is_finite((float) $value)) {
            $feature['invalid_numeric']++;

            return;
        }
        $number = (float) $value;
        $feature['values'][] = $number;
        $count = count($feature['values']);
        // Welford's algorithm avoids subtracting two large squared sums.
        $delta = $number - $feature['mean_acc'];
        $feature['mean_acc'] += $delta / $count;
        $feature['m2'] += $delta * ($number - $feature['mean_acc']);
    }

    private function finalize(array $features, int $rows): array
    {
        foreach ($features as &$feature) {
            sort($feature['values'], SORT_NUMERIC);
            $values = $feature['values'];
            $count = count($values);
            $feature['total_rows'] = $rows;
            $feature['missing'] = $rows - $feature['non_missing'];
            $feature['completeness_percent'] = $rows > 0 ? round(100 * $feature['non_missing'] / $rows, 2) : null;
            $feature['missing_percent'] = $rows > 0 ? round(100 * $feature['missing'] / $rows, 2) : null;
            $feature['numeric_count'] = $count;
            $feature['min'] = $count ? $values[0] : null;
            $feature['max'] = $count ? $values[$count - 1] : null;
            $feature['mean'] = $count ? $feature['mean_acc'] : null;
            $middle = intdiv($count, 2);
            $feature['median'] = $count ? ($count % 2 ? $values[$middle] : ($values[$middle - 1] / 2 + $values[$middle] / 2)) : null;
            $feature['sample_stddev'] = $count > 1 ? sqrt(max(0.0, $feature['m2']) / ($count - 1)) : null;
            unset($feature['values'], $feature['mean_acc'], $feature['m2']);
        }
        unset($feature);

        return $features;
    }

    private function cellCompleteness(array $features, int $rows): ?float
    {
        return $rows > 0 ? round(100 * array_sum(array_column($features, 'non_missing')) / ($rows * count($features)), 2) : null;
    }

    private function write($stream, array $row): void
    {
        if (fputcsv($stream, $row, ',', '"', '') === false) {
            throw new RuntimeException('Could not write report CSV.');
        }
    }
}
