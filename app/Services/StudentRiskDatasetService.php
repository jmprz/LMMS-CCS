<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StudentRiskDatasetService
{
    public function __construct(private StudentRiskFeatureService $features) {}

    /**
     * Write a scalar, student-level dataset to an open stream. Nulls become empty cells.
     * Topic performance uses fixed JSON columns; class content counts are descriptive metadata.
     * Optional snapshot writes are rolled back together if any row fails.
     *
     * @param  resource  $stream
     */
    public function exportCsv(int $labSessionId, CarbonInterface $cutoff, $stream, bool $saveSnapshots = false, bool $mlReady = false): int
    {
        if (! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('CSV output must be an open writable stream.');
        }

        $cutoff = Carbon::instance($cutoff)->startOfSecond();

        return DB::transaction(function () use ($labSessionId, $cutoff, $stream, $saveSnapshots, $mlReady) {
            if (! DB::table('lab_sessions')->where('id', $labSessionId)->exists()) {
                throw new InvalidArgumentException("Lab session {$labSessionId} does not exist.");
            }

            $columns = app(RiskFeatureSchema::class)->columns($mlReady);
            $this->writeRow($stream, $columns);

            $studentIds = DB::table('class_student as cs')
                ->join('users as u', 'u.id', '=', 'cs.user_id')
                ->where('cs.lab_session_id', $labSessionId)
                ->where('u.role', 'student')
                ->where('cs.joined_at', '<=', $cutoff)
                ->distinct()->orderBy('cs.user_id')->pluck('cs.user_id');

            $contentCounts = $this->contentCounts($labSessionId, $cutoff);
            foreach ($studentIds as $userId) {
                $features = $saveSnapshots
                    ? $this->features->snapshot((int) $userId, $labSessionId, $cutoff)->toArray()
                    : $this->features->build((int) $userId, $labSessionId, $cutoff);
                // Projection is selected by the schema; retain supporting metadata values.
                $input = $this->features->diagnosticInput($features);
                $row = [
                    'user_id' => (int) $userId,
                    'lab_session_id' => $labSessionId,
                    'feature_cutoff_at' => $cutoff->format('Y-m-d H:i:s'),
                    'feature_version' => $features['feature_version'],
                    ...$input,
                    'eligible_session_count' => (int) $features['eligible_session_count'],
                ];
                $row = [...$row, ...$this->topicColumns($features), ...$contentCounts];
                if ($mlReady) {
                    $row['student_group_key'] = app(RiskFeatureSchema::class)->groupingKey((int) $userId);
                }
                $this->writeRow($stream, array_map(fn ($column) => $row[$column] ?? null, $columns));
            }

            if (! fflush($stream)) {
                throw new RuntimeException('Could not flush CSV output.');
            }

            return $studentIds->count();
        });
    }

    /**
     * Export one row per distinct student/class pair, using fixed topic JSON columns.
     * Null class IDs select active classes with students enrolled by the shared cutoff.
     *
     * @param  resource  $stream
     * @return array{classes: array, row_count: int}
     */
    public function exportClassesCsv(?array $classIds, CarbonInterface $cutoff, $stream, bool $saveSnapshots = false, bool $mlReady = false): array
    {
        if (! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('CSV output must be an open writable stream.');
        }
        if ($classIds !== null) {
            if ($classIds === [] || count(array_filter($classIds, fn ($id) => is_int($id) && $id > 0)) !== count($classIds)) {
                throw new InvalidArgumentException('Class IDs must be positive integers.');
            }
            $classIds = array_values(array_unique($classIds));
        }
        $cutoff = Carbon::instance($cutoff)->startOfSecond();

        return DB::transaction(function () use ($classIds, $cutoff, $stream, $saveSnapshots, $mlReady) {
            $query = DB::table('lab_sessions as ls');
            if ($classIds === null) {
                $query->where('ls.is_active', true)->whereExists(function ($enrolled) use ($cutoff) {
                    $enrolled->selectRaw('1')->from('class_student as cs')
                        ->join('users as u', 'u.id', '=', 'cs.user_id')
                        ->whereColumn('cs.lab_session_id', 'ls.id')
                        ->where('u.role', 'student')
                        ->where('cs.joined_at', '<=', $cutoff);
                });
            } else {
                $query->whereIn('ls.id', $classIds);
            }
            $classes = $query->orderBy('ls.id')->get([
                'ls.id', 'ls.class_code', 'ls.subject_name', 'ls.program', 'ls.year_level', 'ls.section',
            ]);
            if ($classIds !== null && $classes->count() !== count($classIds)) {
                $missing = array_diff($classIds, $classes->pluck('id')->all());
                throw new InvalidArgumentException('Lab sessions do not exist: '.implode(', ', $missing));
            }
            $columns = app(RiskFeatureSchema::class)->columns($mlReady, true);
            $this->writeRow($stream, $columns);
            $summary = [];
            $total = 0;
            foreach ($classes as $class) {
                $studentIds = DB::table('class_student as cs')
                    ->join('users as u', 'u.id', '=', 'cs.user_id')
                    ->where('cs.lab_session_id', $class->id)
                    ->where('u.role', 'student')
                    ->where('cs.joined_at', '<=', $cutoff)
                    ->distinct()->orderBy('cs.user_id')->pluck('cs.user_id');
                $contentCounts = $this->contentCounts((int) $class->id, $cutoff);
                foreach ($studentIds as $userId) {
                    $features = $saveSnapshots
                        ? $this->features->snapshot((int) $userId, (int) $class->id, $cutoff)->toArray()
                        : $this->features->build((int) $userId, (int) $class->id, $cutoff);
                    $input = $this->features->diagnosticInput($features);
                    $row = [
                        'user_id' => (int) $userId,
                        'lab_session_id' => (int) $class->id,
                        'class_code' => $class->class_code,
                        'subject_name' => $class->subject_name,
                        'program' => $class->program,
                        'year_level' => $class->year_level,
                        'section' => $class->section,
                        'feature_cutoff_at' => $cutoff->format('Y-m-d H:i:s'),
                        'feature_version' => $features['feature_version'],
                        ...$input,
                        'eligible_session_count' => (int) $features['eligible_session_count'],
                    ];
                    $row = [...$row, ...$this->topicColumns($features), ...$contentCounts];
                    if ($mlReady) {
                        $row['student_group_key'] = app(RiskFeatureSchema::class)->groupingKey((int) $userId);
                    }
                    $this->writeRow($stream, array_map(fn ($column) => $row[$column] ?? null, $columns));
                }
                $summary[] = [
                    'lab_session_id' => (int) $class->id,
                    'class_code' => $class->class_code,
                    'subject_name' => $class->subject_name,
                    'row_count' => $studentIds->count(),
                ];
                $total += $studentIds->count();
            }
            if (! fflush($stream)) {
                throw new RuntimeException('Could not flush CSV output.');
            }

            return ['classes' => $summary, 'row_count' => $total];
        });
    }

    private function topicColumns(array $features): array
    {
        $additional = $features['additional_features'] ?? [];

        return app(RiskFeatureSchema::class)->topicColumns($additional);
    }

    private function contentCounts(int $classId, CarbonInterface $cutoff): array
    {
        $counts = [];
        foreach (['quiz' => 'quizzes', 'task' => 'tasks'] as $kind => $table) {
            $base = DB::table($table)->where('subject_id', $classId)->where('created_at', '<=', $cutoff);
            $counts['tagged_'.$kind.'_count'] = (clone $base)->whereNotNull('learning_topic_id')->count();
            $counts['untagged_'.$kind.'_count'] = (clone $base)->whereNull('learning_topic_id')->count();
        }

        return $counts;
    }

    private function writeRow($stream, array $row): void
    {
        if (fputcsv($stream, $row, ',', '"', '') === false) {
            throw new RuntimeException('Could not write CSV output.');
        }
    }
}
