<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RiskSourceDataAuditService
{
    public function audit(?array $classIds, CarbonInterface $cutoff): array
    {
        $cutoff = Carbon::instance($cutoff)->startOfSecond();
        if ($classIds !== null) {
            if ($classIds === [] || count(array_filter($classIds, fn ($id) => is_int($id) && $id > 0)) !== count($classIds)) {
                throw new InvalidArgumentException('Class IDs must be positive integers.');
            }
            $classIds = array_values(array_unique($classIds));
        }

        return DB::transaction(function () use ($classIds, $cutoff) {
            $query = DB::table('lab_sessions as ls');
            if ($classIds === null) {
                $query->where('ls.is_active', true)->whereExists(function ($q) use ($cutoff) {
                    $q->selectRaw('1')->from('class_student as cs')->join('users as u', 'u.id', '=', 'cs.user_id')
                        ->whereColumn('cs.lab_session_id', 'ls.id')->where('u.role', 'student')->where('cs.joined_at', '<=', $cutoff);
                });
            } else {
                $query->whereIn('ls.id', $classIds);
            }
            $classes = $query->orderBy('ls.id')->get(['ls.id', 'ls.class_code', 'ls.subject_name']);
            if ($classIds !== null && $classes->count() !== count($classIds)) {
                throw new InvalidArgumentException('One or more requested lab sessions do not exist.');
            }
            $results = [];
            foreach ($classes as $class) {
                $results[] = [
                    'lab_session_id' => (int) $class->id, 'class_code' => $class->class_code,
                    'subject_name' => $class->subject_name,
                    ...$this->scope([$class->id], $cutoff),
                ];
            }

            return [
                'cutoff' => $cutoff->format('Y-m-d H:i:s'),
                'timezone' => config('app.timezone'),
                'classes' => $results,
                // Recompute pooled statistics and distinct counts instead of averaging class medians.
                'overall' => $this->scope($classes->pluck('id')->all(), $cutoff),
                'class_count' => $classes->count(),
                'topic_task_supported' => false,
            ];
        });
    }

    private function scope(array $classIds, CarbonInterface $cutoff): array
    {
        $enrollment = DB::table('class_student as cs')->join('users as u', 'u.id', '=', 'cs.user_id')
            ->whereIn('cs.lab_session_id', $classIds)->where('u.role', 'student')->where('cs.joined_at', '<=', $cutoff)
            ->get(['cs.user_id', 'cs.lab_session_id', 'cs.violation_count']);
        $pairs = $enrollment->unique(fn ($row) => $row->user_id.':'.$row->lab_session_id);
        $quizzes = DB::table('quizzes')->whereIn('subject_id', $classIds)->get(['id', 'subject_id', 'created_at', 'published_at', 'learning_topic_id']);
        $cutoffQuizzes = $quizzes->filter(fn ($row) => $this->before($row->created_at, $cutoff));
        $attempts = $this->studentScope(DB::table('quiz_attempts as qa')->join('quizzes as q', 'q.id', '=', 'qa.quiz_id'), 'qa.user_id', 'q.subject_id', $cutoff)
            ->whereIn('q.subject_id', $classIds)->where('qa.is_simulated', false)->where('qa.created_at', '<=', $cutoff)
            ->get(['qa.id', 'qa.user_id', 'qa.quiz_id', 'qa.score', 'qa.total_points', 'qa.updated_at', 'q.subject_id', 'q.created_at as quiz_created_at', 'q.learning_topic_id']);
        $validAttempts = $attempts->filter(fn ($row) => $this->before($row->quiz_created_at, $cutoff)
            && $this->before($row->updated_at, $cutoff) && $row->score !== null && (float) $row->total_points > 0);
        $taggedAttempts = $attempts->whereNotNull('learning_topic_id')->filter(fn ($row) => $this->before($row->quiz_created_at, $cutoff));
        $topics = DB::table('learning_topics')->whereIn('id', $cutoffQuizzes->pluck('learning_topic_id')->filter()->unique())
            ->pluck('name', 'id');
        $topicRows = [];
        foreach ($cutoffQuizzes->whereNotNull('learning_topic_id')->groupBy('learning_topic_id') as $topicId => $group) {
            $topicAttempts = $taggedAttempts->where('learning_topic_id', $topicId);
            $topicRows[] = ['topic_id' => (int) $topicId, 'topic_name' => $topics[$topicId] ?? '(missing topic record)',
                'quizzes' => $group->count(), 'real_attempts' => $topicAttempts->count(),
                'students' => $topicAttempts->pluck('user_id')->unique()->count()];
        }

        $tasks = DB::table('tasks')->whereIn('subject_id', $classIds)->get(['id', 'subject_id', 'created_at', 'deadline', 'points']);
        $due = $tasks->filter(fn ($row) => $this->before($row->created_at, $cutoff) && $this->before($row->deadline, $cutoff));
        $submissions = $this->studentScope(DB::table('submissions as s')->join('tasks as t', 't.id', '=', 's.task_id'), 's.user_id', 't.subject_id', $cutoff)
            ->whereIn('t.subject_id', $classIds)->whereRaw('COALESCE(s.submitted_at, s.created_at) <= ?', [$cutoff])
            ->get(['s.id', 's.user_id', 's.task_id', 's.grade', 's.updated_at', 's.duration_seconds', 's.submitted_at', 's.created_at',
                't.points', 't.subject_id', 't.created_at as task_created_at']);
        $eligible = $submissions->whereIn('task_id', $due->pluck('id')->all());
        $latest = $eligible->sortByDesc('id')->unique(fn ($row) => $row->user_id.':'.$row->task_id);
        $rubrics = DB::table('submission_grades')->whereIn('submission_id', $eligible->pluck('id'))
            ->get(['submission_id', 'total_score', 'max_score', 'created_at', 'updated_at'])->groupBy('submission_id');
        $rubricIds = [];
        $fallbackIds = [];
        $usableIds = [];
        foreach ($eligible as $submission) {
            $validRubric = ($rubrics[$submission->id] ?? collect())->contains(fn ($grade) => $grade->total_score !== null
                && (float) $grade->max_score > 0 && $this->before($grade->created_at, $cutoff) && $this->before($grade->updated_at, $cutoff));
            $fallback = ! $validRubric && $submission->grade !== null && (float) $submission->points > 0
                && $this->before($submission->updated_at, $cutoff);
            if ($validRubric) {
                $rubricIds[] = $submission->id;
            }
            if ($fallback) {
                $fallbackIds[] = $submission->id;
            }
            if ($validRubric || $fallback) {
                $usableIds[] = $submission->id;
            }
        }

        $attendance = $this->studentScope(DB::table('attendances as a'), 'a.user_id', 'a.lab_session_id', $cutoff)
            ->whereIn('a.lab_session_id', $classIds)->whereDate('a.attendance_date', '<=', $cutoff->toDateString())->where('a.created_at', '<=', $cutoff)
            ->get(['a.user_id', 'a.lab_session_id', 'a.attendance_date', 'a.status']);
        $materials = DB::table('materials')->whereIn('lab_session_id', $classIds)->get(['id', 'created_at']);
        $materialLogs = $this->studentScope(DB::table('material_logs as ml')->join('materials as m', 'm.id', '=', 'ml.material_id'), 'ml.user_id', 'm.lab_session_id', $cutoff)
            ->whereIn('m.lab_session_id', $classIds)->where('ml.opened_at', '<=', $cutoff)
            ->get(['ml.id', 'ml.user_id', 'ml.material_id', 'ml.opened_at', 'ml.duration_seconds', 'ml.updated_at', 'ml.closed_at',
                'ml.collection_version', 'ml.measurement_mode', 'ml.last_heartbeat_at', 'm.type']);
        $activity = $this->studentScope(DB::table('activity_logs as a'), 'a.user_id', 'a.lab_session_id', $cutoff)
            ->whereIn('a.lab_session_id', $classIds)->where('a.created_at', '<=', $cutoff)
            ->get(['a.user_id', 'a.lab_session_id', 'a.log_type', 'a.duration_seconds', 'a.created_at', 'a.updated_at', 'a.ended_at']);
        $availableActivity = $activity->filter(fn ($row) => $this->before($row->updated_at, $cutoff));
        $verifiedActivity = $this->studentScope(app(ActivityDurationService::class)->evidenceQuery($cutoff),
            'activity_duration_sessions.user_id', 'activity_duration_sessions.lab_session_id', $cutoff)
            ->whereIn('lab_session_id', $classIds)->get(['user_id', 'lab_session_id', 'duration_seconds']);
        $verifiedPairTotals = $pairs->map(function ($pair) use ($verifiedActivity) {
            $sessions = $verifiedActivity->filter(fn ($row) => $row->user_id === $pair->user_id && $row->lab_session_id === $pair->lab_session_id);
            return $sessions->isEmpty() ? null : (float) $sessions->sum('duration_seconds');
        });
        $featureEvents = $activity->whereIn('log_type', ['violation', 'alert']);
        $blocked = $this->studentScope(DB::table('blocked_attempts as b'), 'b.user_id', 'b.lab_session_id', $cutoff)
            ->whereIn('b.lab_session_id', $classIds)->where('b.attempted_at', '<=', $cutoff)->get(['b.user_id']);
        $gradeRows = $this->studentScope(DB::table('subject_grades as g'), 'g.user_id', 'g.lab_session_id', $cutoff)
            ->whereIn('g.lab_session_id', $classIds)->get(['g.user_id', 'g.grading_period', 'g.grade', 'g.recorded_at', 'g.created_at', 'g.updated_at']);
        $usableGrade = fn ($row) => strtolower(trim($row->grading_period)) === 'midterm' && $row->grade !== null
            && $this->before($row->recorded_at, $cutoff) && $this->before($row->created_at, $cutoff) && $this->before($row->updated_at, $cutoff);
        $periods = [];
        foreach ($gradeRows->groupBy('grading_period') as $period => $group) {
            $periods[] = ['grading_period' => $period, 'records' => $group->count(), 'students' => $group->pluck('user_id')->unique()->count(),
                'recorded_before_cutoff' => $group->filter(fn ($row) => $this->before($row->recorded_at, $cutoff))->count()];
        }
        $typeStats = [];
        foreach ($activity->groupBy('log_type') as $type => $group) {
            $typeStats[] = ['log_type' => $type, 'logs' => $group->count(), ...$this->durationStats($group->pluck('duration_seconds')),
                'over_8h' => $group->filter(fn ($row) => $row->duration_seconds > 28800)->count(),
                'over_24h' => $group->filter(fn ($row) => $row->duration_seconds > 86400)->count(),
                'missing_ended_at' => $group->whereNull('ended_at')->count(),
                'duration_with_unchanged_timestamp' => $group->filter(fn ($row) => $row->duration_seconds > 0 && $row->updated_at === $row->created_at)->count()];
        }
        // Include zero-duration students; these are the dataset's student-class sum distribution.
        $pairTotals = $pairs->map(fn ($pair) => (float) $availableActivity->filter(fn ($row) => $row->user_id === $pair->user_id
            && $row->lab_session_id === $pair->lab_session_id)->sum('duration_seconds'));
        $featureCounts = $pairs->map(fn ($pair) => $featureEvents->filter(fn ($row) => $row->user_id === $pair->user_id && $row->lab_session_id === $pair->lab_session_id)->count());
        $metrics = [
            'activity_logs_excluded_from_v5_duration' => $activity->count(),
            'activity_log_durations_excluded_from_v5' => $activity->whereNotNull('duration_seconds')->count(),
            'activity_v5_verified_sessions' => $verifiedActivity->count(),
            'activity_v5_students_with_duration' => $verifiedActivity->pluck('user_id')->unique()->count(),
            'activity_v5_student_class_rows_with_duration' => $verifiedPairTotals->filter(fn ($value) => $value !== null)->count(),
            'activity_v5_student_class_rows_null_duration' => $verifiedPairTotals->filter(fn ($value) => $value === null)->count(),
            'activity_v5_coverage_percent' => $pairs->isEmpty() ? null : round(100 * $verifiedPairTotals->filter(fn ($value) => $value !== null)->count() / $pairs->count(), 2),
            'enrolled_student_class_pairs' => $pairs->count(), 'unique_students' => $pairs->pluck('user_id')->unique()->count(),
            'quizzes_total_current' => $quizzes->count(), 'quizzes_created_by_cutoff' => $cutoffQuizzes->count(),
            'quizzes_published_by_cutoff' => $cutoffQuizzes->filter(fn ($row) => $this->before($row->published_at, $cutoff))->count(),
            'quizzes_topic_tagged_by_cutoff' => $cutoffQuizzes->whereNotNull('learning_topic_id')->count(),
            'quizzes_untagged_by_cutoff' => $cutoffQuizzes->whereNull('learning_topic_id')->count(),
            'real_quiz_attempts_before_cutoff' => $attempts->count(),
            'students_with_real_quiz_attempts' => $attempts->pluck('user_id')->unique()->count(),
            'students_with_usable_quiz_percentages' => $validAttempts->pluck('user_id')->unique()->count(),
            'real_attempts_unusable_by_v2' => $attempts->count() - $validAttempts->count(),
            'students_attempting_tagged_quizzes' => $taggedAttempts->pluck('user_id')->unique()->count(),
            'tasks_total_current' => $tasks->count(), 'tasks_due_by_cutoff' => $due->count(),
            'submissions_before_cutoff' => $submissions->count(), 'eligible_submission_rows' => $eligible->count(),
            'latest_eligible_submissions' => $latest->count(), 'students_with_eligible_submissions' => $eligible->pluck('user_id')->unique()->count(),
            'eligible_submissions_with_rubric_records' => $eligible->filter(fn ($row) => isset($rubrics[$row->id]))->count(),
            'eligible_submissions_using_rubric_score' => count($rubricIds), 'eligible_submissions_using_fallback_grade' => count($fallbackIds),
            'eligible_graded_submissions' => count($usableIds), 'eligible_ungraded_submissions' => $eligible->count() - count($usableIds),
            'students_with_usable_latest_task_percentages' => $latest->whereIn('id', $usableIds)->pluck('user_id')->unique()->count(),
            'submission_durations_equal_task_age' => $submissions->filter(function ($row) {
                if ($row->duration_seconds === null || $row->task_created_at === null) {
                    return false;
                }
                $age = max(0, Carbon::parse($row->submitted_at ?? $row->created_at)->getTimestamp()
                    - Carbon::parse($row->task_created_at)->getTimestamp());

                return abs((float) $row->duration_seconds - $age) <= 1;
            })->count(),
            'topic_task_supported' => 0,
            'attendance_distinct_dates' => $attendance->pluck('attendance_date')->unique()->count(),
            'attendance_class_dates' => $attendance->unique(fn ($row) => $row->lab_session_id.':'.$row->attendance_date)->count(),
            'attendance_rows' => $attendance->count(), 'students_with_attendance' => $attendance->pluck('user_id')->unique()->count(),
            'materials_total_current' => $materials->count(), 'materials_created_by_cutoff' => $materials->filter(fn ($row) => $this->before($row->created_at, $cutoff))->count(),
            'material_sessions' => $materialLogs->count(), 'students_with_material_logs' => $materialLogs->pluck('user_id')->unique()->count(),
            'material_logs_with_duration' => $materialLogs->whereNotNull('duration_seconds')->count(),
            'material_logs_null_duration' => $materialLogs->whereNull('duration_seconds')->count(),
            'material_logs_unclosed' => $materialLogs->whereNull('closed_at')->count(),
            'material_logs_closed' => $materialLogs->whereNotNull('closed_at')->count(),
            'material_duration_coverage_percent' => $materialLogs->isEmpty() ? null : round(100 * $materialLogs->whereNotNull('duration_seconds')->count() / $materialLogs->count(), 2),
            'material_logs_zero_duration' => $materialLogs->filter(fn ($row) => $row->duration_seconds !== null && (float) $row->duration_seconds === 0.0)->count(),
            'material_logs_legacy_collection' => $materialLogs->whereNull('collection_version')->count(),
            'material_logs_visible_v1_collection' => $materialLogs->where('collection_version', MaterialEngagementService::VERSION)->count(),
            'material_logs_external_open' => $materialLogs->where('measurement_mode', 'external_open')->count(),
            'material_duration_over_1h' => $materialLogs->filter(fn ($row) => $row->duration_seconds > 3600)->count(),
            'material_duration_over_8h' => $materialLogs->filter(fn ($row) => $row->duration_seconds > 28800)->count(),
            'material_duplicate_open_timestamp_groups' => $materialLogs->groupBy(fn ($row) => $row->user_id.':'.$row->material_id.':'.$row->opened_at)
                ->filter(fn ($group) => $group->count() > 1)->count(),
            'material_multiple_unclosed_pair_groups' => $materialLogs->whereNull('closed_at')
                ->groupBy(fn ($row) => $row->user_id.':'.$row->material_id)->filter(fn ($group) => $group->count() > 1)->count(),
            'material_durations_available_to_v2' => $materialLogs->filter(fn ($row) => $row->duration_seconds !== null && $this->before($row->updated_at, $cutoff))->count(),
            'activity_log_count' => $activity->count(), 'students_with_activity_logs' => $activity->pluck('user_id')->unique()->count(),
            'activity_durations_available_to_v2' => $availableActivity->whereNotNull('duration_seconds')->count(),
            'activity_logs_updated_after_cutoff' => $activity->filter(fn ($row) => $row->updated_at !== null && ! $this->before($row->updated_at, $cutoff))->count(),
            'activity_duration_over_8h' => $activity->filter(fn ($row) => $row->duration_seconds > 28800)->count(),
            'activity_duration_over_24h' => $activity->filter(fn ($row) => $row->duration_seconds > 86400)->count(),
            'activity_logs_without_end' => $activity->whereNull('ended_at')->count(),
            'activity_positive_duration_unchanged_timestamp' => $activity->filter(fn ($row) => $row->duration_seconds > 0 && $row->updated_at === $row->created_at)->count(),
            'blocked_attempts_before_cutoff' => $blocked->count(), 'violation_activity_events' => $featureEvents->count(),
            'students_with_violation_or_blocked_attempt' => $pairs->filter(fn ($row) => (int) $row->violation_count > 0)->pluck('user_id')
                ->merge($blocked->pluck('user_id'))->merge($featureEvents->pluck('user_id'))->unique()->count(),
            'violation_feature_zero_variance' => $featureCounts->isEmpty() ? null : (int) ($featureCounts->min() === $featureCounts->max()),
            'violation_feature_min' => $featureCounts->min(), 'violation_feature_max' => $featureCounts->max(),
            'subject_grade_records_current' => $gradeRows->count(),
            'subject_grade_records_before_cutoff' => $gradeRows->filter(fn ($row) => $this->before($row->recorded_at, $cutoff))->count(),
            'subject_grade_records_after_cutoff' => $gradeRows->filter(fn ($row) => $row->recorded_at !== null && ! $this->before($row->recorded_at, $cutoff))->count(),
            'subject_grade_records_without_recorded_at' => $gradeRows->whereNull('recorded_at')->count(),
            'subject_grades_with_non_null_grade' => $gradeRows->whereNotNull('grade')->count(),
            'subject_grade_midterm_records' => $gradeRows->filter(fn ($row) => strtolower(trim($row->grading_period)) === 'midterm')->count(),
            'subject_grades_updated_after_cutoff' => $gradeRows->filter(fn ($row) => $row->updated_at !== null && ! $this->before($row->updated_at, $cutoff))->count(),
            'students_with_current_academic_grade_source' => $gradeRows->filter($usableGrade)->pluck('user_id')->unique()->count(),
        ];
        foreach (['submission_duration' => $submissions->pluck('duration_seconds'),
            'activity_v5_valid_session_duration' => $verifiedActivity->pluck('duration_seconds'),
            'activity_v5_student_class_total' => $verifiedPairTotals,
            'material_visible_v1_duration' => $materialLogs->where('collection_version', MaterialEngagementService::VERSION)->pluck('duration_seconds'),
            'material_duration' => $materialLogs->pluck('duration_seconds'), 'activity_duration' => $activity->pluck('duration_seconds'),
            'activity_v2_duration' => $availableActivity->pluck('duration_seconds'), 'activity_student_class_total' => $pairTotals] as $prefix => $values) {
            foreach ($this->durationStats($values) as $stat => $value) {
                $metrics[$prefix.'_'.$stat] = $value;
            }
        }

        foreach ($materialLogs->groupBy('type') as $type => $logs) {
            $prefix = 'material_type_'.preg_replace('/[^a-z0-9_]/i', '_', $type ?: 'unknown');
            $metrics[$prefix.'_sessions'] = $logs->count();
            $metrics[$prefix.'_unclosed'] = $logs->whereNull('closed_at')->count();
            $metrics[$prefix.'_null_duration'] = $logs->whereNull('duration_seconds')->count();
        }

        foreach ($activity->groupBy('log_type') as $type => $logs) {
            $prefix = 'activity_type_'.preg_replace('/[^a-z0-9_]/i', '_', $type ?: 'unknown');
            $metrics[$prefix.'_logs'] = $logs->count();
            $metrics[$prefix.'_duration_rows'] = $logs->whereNotNull('duration_seconds')->count();
            $metrics[$prefix.'_excluded_from_v5_duration'] = $logs->count();
        }

        return ['metrics' => $metrics, 'topics' => $topicRows, 'grading_periods' => $periods, 'activity_types' => $typeStats,
            'attendance_statuses' => $this->distribution($attendance->pluck('status')),
            // Live pivot counter can be reset and has no event history; do not treat as cutoff-safe.
            'current_pivot_violation_distribution' => $this->distribution($pairs->pluck('violation_count'))];
    }

    private function studentScope($query, string $userColumn, string $classColumn, CarbonInterface $cutoff)
    {
        return $query->whereExists(function ($q) use ($userColumn, $classColumn, $cutoff) {
            $q->selectRaw('1')->from('class_student as enrollment')->join('users as student', 'student.id', '=', 'enrollment.user_id')
                ->whereColumn('enrollment.user_id', $userColumn)->whereColumn('enrollment.lab_session_id', $classColumn)
                ->where('student.role', 'student')->where('enrollment.joined_at', '<=', $cutoff);
        });
    }

    private function before(?string $value, CarbonInterface $cutoff): bool
    {
        return $value !== null && Carbon::parse($value)->lte($cutoff);
    }

    private function durationStats(Collection $values): array
    {
        $numeric = $values->filter(fn ($value) => $value !== null && is_numeric($value) && is_finite((float) $value))
            ->map(fn ($value) => (float) $value)->sort()->values();

        return ['count' => $numeric->count(), 'null_count' => $values->filter(fn ($value) => $value === null)->count(),
            'negative_count' => $numeric->filter(fn ($value) => $value < 0)->count(),
            'min' => $numeric->min(), 'median' => $numeric->median(), 'mean' => $numeric->isEmpty() ? null : $numeric->avg(), 'max' => $numeric->max()];
    }

    private function distribution(Collection $values): array
    {
        return $values->map(fn ($value) => $value === null ? '(NULL)' : (string) $value)->countBy()->sortKeys()->all();
    }

    public function export(array $report, string $filename): string
    {
        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.csv\z/i', $filename)) {
            throw new InvalidArgumentException('--output must be a CSV filename without directories.');
        }
        $path = storage_path('app/risk-datasets/'.$filename);
        if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true) && ! is_dir(dirname($path))) {
            throw new RuntimeException('Could not create audit output directory.');
        }
        $stream = @fopen($path, 'x');
        if ($stream === false) {
            throw new RuntimeException('Cannot create audit output; choose a new filename.');
        }
        try {
            $columns = ['scope', 'lab_session_id', 'class_code', 'subject_name', 'feature_cutoff_at', 'metric', 'value'];
            $this->write($stream, $columns);
            foreach ([...$report['classes'], ['lab_session_id' => null, 'class_code' => null, 'subject_name' => null, ...$report['overall']]] as $class) {
                foreach ($class['metrics'] as $metric => $value) {
                    $this->write($stream, [$class['lab_session_id'] === null ? 'overall' : 'class', $class['lab_session_id'],
                        $this->safeText($class['class_code']), $this->safeText($class['subject_name']), $report['cutoff'], $metric, $value]);
                }
            }
            if (! fflush($stream)) {
                throw new RuntimeException('Could not flush audit output.');
            }
        } catch (\Throwable $e) {
            fclose($stream);
            @unlink($path);
            throw $e;
        }
        fclose($stream);

        return $path;
    }

    private function safeText(?string $value): ?string
    {
        return $value !== null && preg_match('/\A[\s]*[=+@-]/', $value) ? "'".$value : $value;
    }

    private function write($stream, array $row): void
    {
        if (fputcsv($stream, $row, ',', '"', '') === false) {
            throw new RuntimeException('Could not write audit CSV.');
        }
    }
}
