<?php

namespace App\Services;

use InvalidArgumentException;

/** Aggregate topic coverage without exposing student identifiers or topic names. */
class RiskTopicCoverageService
{
    private array $classes = [];

    public function observe(array $row): void
    {
        $classId = $row['lab_session_id'];
        $userId = $row['student_group_key'] ?? $row['user_id'];
        $class = &$this->classes[$classId];
        $class ??= ['students' => [], 'quiz' => [], 'task' => [], 'counts' => []];
        $class['students'][$userId] = true;
        foreach (['quiz' => 'attempt_count', 'task' => 'graded_task_count'] as $kind => $countKey) {
            $column = 'topic_'.$kind.'_performance_json';
            $present = false;
            if (isset($row[$column]) && trim($row[$column]) !== '') {
                try {
                    $topics = json_decode($row[$column], false, 32, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new InvalidArgumentException('Topic performance contains invalid JSON.');
                }
                if (! $topics instanceof \stdClass && $topics !== []) {
                    throw new InvalidArgumentException('Topic performance must be an object keyed by topic ID.');
                }
                foreach ($topics as $id => $topic) {
                    if (! preg_match('/\A[1-9][0-9]*\z/', (string) $id)
                        || ! $topic instanceof \stdClass || ! isset($topic->average, $topic->{$countKey})
                        || ! is_numeric($topic->average) || ! is_finite((float) $topic->average)
                        || ! is_int($topic->{$countKey}) || $topic->{$countKey} < 1) {
                        throw new InvalidArgumentException('Topic performance contains an invalid score or count.');
                    }
                    $present = true;
                }
            } elseif ($kind === 'quiz') {
                // Continue accepting archived datasets with dynamic topic columns.
                foreach ($row as $name => $value) {
                    if (preg_match('/\Atopic_quiz_average__topic_[0-9]+\z/', $name) && is_numeric($value)) {
                        $present = true;
                    }
                }
            }
            if ($present) {
                $class[$kind][$userId] = true;
            }
            foreach (['tagged', 'untagged'] as $tag) {
                $key = $tag.'_'.$kind.'_count';
                if (! isset($row[$key]) || $row[$key] === '') {
                    continue;
                }
                if (! preg_match('/\A[0-9]+\z/', $row[$key])) {
                    throw new InvalidArgumentException('Class topic tagging counts must be nonnegative integers.');
                }
                $count = (int) $row[$key];
                if (isset($class['counts'][$key]) && $class['counts'][$key] !== $count) {
                    throw new InvalidArgumentException('Class topic tagging counts disagree across dataset rows.');
                }
                $class['counts'][$key] = $count;
            }
        }
    }

    public function summary(): array
    {
        $rows = [];
        foreach (['quiz', 'task'] as $kind) {
            $unique = [];
            $pairs = 0;
            $tagged = 0;
            $untagged = 0;
            $known = true;
            foreach ($this->classes as $classId => $class) {
                $unique += $class[$kind];
                $count = count($class[$kind]);
                $pairs += $count;
                $tag = $class['counts']['tagged_'.$kind.'_count'] ?? null;
                $untag = $class['counts']['untagged_'.$kind.'_count'] ?? null;
                $known = $known && $tag !== null && $untag !== null;
                $tagged += $tag ?? 0;
                $untagged += $untag ?? 0;
                $rows[] = ['scope' => 'class', 'lab_session_id' => $classId, 'kind' => $kind,
                    'student_class_rows_with_score' => $count, 'unique_students_with_score' => $count,
                    'tagged_items' => $tag, 'untagged_items' => $untag];
            }
            array_unshift($rows, ['scope' => 'overall', 'lab_session_id' => null, 'kind' => $kind,
                'student_class_rows_with_score' => $pairs, 'unique_students_with_score' => count($unique),
                'tagged_items' => $known && $this->classes !== [] ? $tagged : null,
                'untagged_items' => $known && $this->classes !== [] ? $untagged : null]);
        }

        return $rows;
    }
}
