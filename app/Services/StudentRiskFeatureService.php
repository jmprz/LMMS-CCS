<?php

namespace App\Services;

use App\Models\StudentFeatureSnapshot;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

// Research freeze: v5 is frozen for live study collection. Semantic changes require
// a new feature version; see docs/research-freeze-v5.md before changing formulas.
class StudentRiskFeatureService
{
    public const FEATURE_VERSION = 'v5';

    public function build(int $userId, int $labSessionId, ?CarbonInterface $cutoff = null): array
    {
        $cutoff = $cutoff ? Carbon::instance($cutoff) : now();

        $this->assertStudentBelongsToClass($userId, $labSessionId);

        $quiz = $this->quizFeatures($userId, $labSessionId, $cutoff);
        $reading = $this->readingFeatures($userId, $labSessionId, $cutoff);
        $attendance = $this->attendanceFeatures($userId, $labSessionId, $cutoff);
        $tasks = $this->taskFeatures($userId, $labSessionId, $cutoff);
        $activity = $this->activityFeatures($userId, $labSessionId, $cutoff);

        return [
            'user_id' => $userId,
            'lab_session_id' => $labSessionId,
            'feature_cutoff_at' => $cutoff->copy(),
            'feature_version' => self::FEATURE_VERSION,

            'quiz_avg' => $quiz['quiz_avg'],
            'quiz_attempt_count' => $quiz['quiz_attempt_count'],

            'reading_avg_seconds' => $reading['reading_avg_seconds'],
            'reading_total_seconds' => $reading['reading_total_seconds'],
            'material_session_count' => $reading['material_session_count'],
            'materials_accessed' => $reading['materials_accessed'],

            'attendance_rate' => $attendance['attendance_rate'],
            'eligible_session_count' => $attendance['eligible_session_count'],

            'task_avg' => $tasks['task_avg'],
            'submission_rate' => $tasks['submission_rate'],
            'assigned_task_count' => $tasks['assigned_task_count'],

            'additional_features' => [
                'current_academic_grade' => $this->currentAcademicGrade($userId, $labSessionId, $cutoff),
                'violation_count' => $this->violationCount($userId, $labSessionId, $cutoff),
                'topic_quiz_average' => $quiz['topic_quiz_average'],
                'topic_quiz_performance' => $quiz['topic_quiz_performance'],
                'topic_task_performance' => $tasks['topic_task_performance'],
                // Scalar encoding remains undecided; detailed performance is stored separately.
                'topic_task_average' => null,
                'late_submission_count' => $tasks['late_submission_count'],
                'activity_count' => $activity['activity_count'],
                'activity_duration_seconds' => $activity['activity_duration_seconds'],
            ],
        ];
    }

    public function snapshot(int $userId, int $labSessionId, ?CarbonInterface $cutoff = null): StudentFeatureSnapshot
    {
        $features = $this->build($userId, $labSessionId, $cutoff);

        return StudentFeatureSnapshot::firstOrCreate(
            array_intersect_key($features, array_flip([
                'user_id', 'lab_session_id', 'feature_cutoff_at', 'feature_version',
            ])),
            $features
        );
    }

    public function modelInput(array|StudentFeatureSnapshot $features): array
    {
        $schema = app(RiskFeatureSchema::class);
        $values = $this->diagnosticInput($features);
        $additional = $features instanceof StudentFeatureSnapshot ? ($features->additional_features ?? []) : ($features['additional_features'] ?? []);
        return [...array_intersect_key($values, array_flip([...$schema->core(), ...$schema->optional()])),
            ...$schema->topicColumns($additional)];
    }

    /** Historical diagnostic projection; excluded variables are not ML inputs. */
    public function diagnosticInput(array|StudentFeatureSnapshot $features): array
    {
        $get = fn (string $key) => $features instanceof StudentFeatureSnapshot
            ? $features->{$key}
            : ($features[$key] ?? null);

        $additional = $get('additional_features') ?? [];

        return [
            'current_academic_grade' => $this->nullableFloat($additional['current_academic_grade'] ?? null),
            'violation_count' => isset($additional['violation_count']) ? (int) $additional['violation_count'] : null,
            'topic_quiz_average' => $additional['topic_quiz_average'] ?? [],
            'topic_task_average' => null,
            'late_submission_count' => isset($additional['late_submission_count']) ? (int) $additional['late_submission_count'] : null,
            'activity_count' => isset($additional['activity_count']) ? (int) $additional['activity_count'] : null,
            'activity_duration_seconds' => isset($additional['activity_duration_seconds']) ? (int) $additional['activity_duration_seconds'] : null,
            'quiz_avg' => $this->nullableFloat($get('quiz_avg')),
            'quiz_attempt_count' => (int) ($get('quiz_attempt_count') ?? 0),
            'reading_avg_seconds' => $this->nullableFloat($get('reading_avg_seconds')),
            'reading_total_seconds' => (int) ($get('reading_total_seconds') ?? 0),
            'material_session_count' => (int) ($get('material_session_count') ?? 0),
            'materials_accessed' => (int) ($get('materials_accessed') ?? 0),
            'attendance_rate' => $this->nullableFloat($get('attendance_rate')),
            'task_avg' => $this->nullableFloat($get('task_avg')),
            'submission_rate' => $this->nullableFloat($get('submission_rate')),
            'assigned_task_count' => (int) ($get('assigned_task_count') ?? 0),
        ];
    }

    private function quizFeatures(int $userId, int $labSessionId, CarbonInterface $cutoff): array
    {
        $latestAttemptIds = DB::table('quiz_attempts as qa')
            ->join('quizzes as q', 'q.id', '=', 'qa.quiz_id')
            ->where('qa.user_id', $userId)
            ->where('q.subject_id', $labSessionId)
            ->where('q.created_at', '<=', $cutoff)
            ->where('qa.is_simulated', false)
            ->where('qa.created_at', '<=', $cutoff)
            ->where('qa.updated_at', '<=', $cutoff)
            ->where('qa.total_points', '>', 0)
            ->whereNotNull('qa.score')
            ->groupBy('qa.quiz_id')
            ->selectRaw('MAX(qa.id) as id');

        $attempts = DB::table('quiz_attempts as qa')
            ->joinSub($latestAttemptIds, 'latest', fn ($join) => $join->on('latest.id', '=', 'qa.id'))
            ->join('quizzes as q', 'q.id', '=', 'qa.quiz_id')
            ->leftJoin('learning_topics as topic', 'topic.id', '=', 'q.learning_topic_id')
            ->where('qa.total_points', '>', 0)
            ->select('qa.score', 'qa.total_points', 'q.learning_topic_id', 'topic.name as topic_name')
            ->get();

        $percentages = $attempts
            ->map(fn ($row) => ((float) $row->score / (float) $row->total_points) * 100.0)
            ->filter(fn ($value) => is_finite($value));

        return [
            'quiz_avg' => $percentages->isEmpty() ? null : round($percentages->avg(), 2),
            'quiz_attempt_count' => $attempts->count(),
            'topic_quiz_performance' => $attempts
                ->filter(fn ($row) => $row->learning_topic_id !== null && $row->topic_name !== null)
                ->groupBy('learning_topic_id')->map(fn ($rows) => [
                    'topic_name' => $rows->first()->topic_name,
                    'average' => round($rows->avg(fn ($row) => ((float) $row->score / (float) $row->total_points) * 100.0), 2),
                    'attempt_count' => $rows->count(),
                ])->all(),
            'topic_quiz_average' => $attempts
                ->filter(fn ($row) => $row->learning_topic_id !== null)
                ->groupBy('learning_topic_id')
                ->map(fn ($rows) => round($rows->avg(
                    fn ($row) => ((float) $row->score / (float) $row->total_points) * 100.0
                ), 2))
                ->all(),
        ];
    }

    private function readingFeatures(int $userId, int $labSessionId, CarbonInterface $cutoff): array
    {
        $logs = DB::table('material_logs as ml')
            ->join('materials as m', 'm.id', '=', 'ml.material_id')
            ->where('ml.user_id', $userId)
            ->where('m.lab_session_id', $labSessionId)
            ->where('ml.opened_at', '<=', $cutoff)
            ->select('ml.material_id')
            ->selectRaw('CASE WHEN ml.updated_at <= ? THEN ml.duration_seconds ELSE NULL END as duration_seconds', [$cutoff])
            ->get();

        $durations = $logs->pluck('duration_seconds')
            ->filter(fn ($seconds) => $seconds !== null)
            ->map(fn ($seconds) => max(0, (int) $seconds));

        return [
            'reading_avg_seconds' => $durations->isEmpty() ? null : round($durations->avg(), 2),
            'reading_total_seconds' => (int) $durations->sum(),
            'material_session_count' => $logs->count(),
            'materials_accessed' => $logs->pluck('material_id')->unique()->count(),
        ];
    }

    private function attendanceFeatures(int $userId, int $labSessionId, CarbonInterface $cutoff): array
    {
        // Eligibility comes only from the frozen roster and when finalization was known.
        // Multiple meetings on the same date are separate attendance opportunities.
        $statuses = DB::table('attendance_session_students as roster')
            ->join('attendance_sessions as session', 'session.id', '=', 'roster.attendance_session_id')
            ->where('roster.user_id', $userId)
            ->where('session.lab_session_id', $labSessionId)
            ->where('session.status', 'finalized')
            ->where('session.finalized_at', '<=', $cutoff)
            ->pluck('roster.status');

        $pendingCount = $statuses->filter(fn ($status) => $status === 'pending')->count();
        if ($pendingCount > 0) {
            // Deliberately omit student identifiers from anomaly logs.
            Log::warning('Finalized attendance contains pending roster entries; excluded from risk features.', [
                'lab_session_id' => $labSessionId,
                'feature_cutoff_at' => $cutoff->format('Y-m-d H:i:s'),
                'pending_entry_count' => $pendingCount,
            ]);
        }

        $eligibleCount = $statuses->filter(fn ($status) => in_array($status, ['present', 'late', 'absent'], true))->count();
        $attendedCount = $statuses->filter(fn ($status) => in_array($status, ['present', 'late'], true))->count();

        return [
            'attendance_rate' => $eligibleCount === 0 ? null : round(($attendedCount / $eligibleCount) * 100.0, 2),
            'eligible_session_count' => $eligibleCount,
        ];
    }

    private function taskFeatures(int $userId, int $labSessionId, CarbonInterface $cutoff): array
    {
        $eligibleTasks = DB::table('tasks')
            ->where('subject_id', $labSessionId)
            ->where('deadline', '<=', $cutoff)
            ->where('created_at', '<=', $cutoff)
            ->select('id', 'deadline', 'points')
            ->get();

        $assignedTaskCount = $eligibleTasks->count();

        if ($assignedTaskCount === 0) {
            return [
                'task_avg' => null,
                'submission_rate' => null,
                'assigned_task_count' => 0,
                'late_submission_count' => 0,
                'topic_task_performance' => [],
            ];
        }

        $taskIds = $eligibleTasks->pluck('id');

        $latestSubmissionIds = DB::table('submissions')
            ->where('user_id', $userId)
            ->whereIn('task_id', $taskIds)
            ->where(function ($query) use ($cutoff) {
                $query->where(function ($q) use ($cutoff) {
                    $q->whereNotNull('submitted_at')->where('submitted_at', '<=', $cutoff);
                })->orWhere(function ($q) use ($cutoff) {
                    $q->whereNull('submitted_at')->where('created_at', '<=', $cutoff);
                });
            })
            ->groupBy('task_id')
            ->selectRaw('MAX(id) as id');

        $submissions = DB::table('submissions as s')
            ->joinSub($latestSubmissionIds, 'latest', fn ($join) => $join->on('latest.id', '=', 's.id'))
            ->join('tasks as t', 't.id', '=', 's.task_id')
            ->leftJoin('learning_topics as topic', 'topic.id', '=', 't.learning_topic_id')
            ->leftJoin('submission_grades as sg', function ($join) use ($cutoff) {
                $join->on('sg.submission_id', '=', 's.id')
                    ->where('sg.created_at', '<=', $cutoff)
                    ->where('sg.updated_at', '<=', $cutoff);
            })
            ->select([
                's.id', 's.task_id', 's.submitted_at', 's.created_at',
                't.deadline', 't.points', 'sg.total_score', 'sg.max_score',
                't.learning_topic_id', 'topic.name as topic_name',
            ])
            ->selectRaw('CASE WHEN s.updated_at <= ? THEN s.grade ELSE NULL END as grade', [$cutoff])
            ->get();

        $percentages = $submissions->map(function ($row) {
            if ($row->total_score !== null && (float) $row->max_score > 0) {
                return ((float) $row->total_score / (float) $row->max_score) * 100.0;
            }

            if ($row->grade !== null && (float) $row->points > 0) {
                return ((float) $row->grade / (float) $row->points) * 100.0;
            }

            return null;
        })->filter(fn ($value) => $value !== null && is_finite($value));

        // Reuse exactly the same usable percentages and grading precedence as task_avg.
        $topicPerformance = $submissions
            ->filter(fn ($row, $key) => $row->learning_topic_id !== null && $row->topic_name !== null && $percentages->has($key))
            ->groupBy('learning_topic_id', preserveKeys: true)
            ->map(fn ($rows) => [
                'topic_name' => $rows->first()->topic_name,
                'average' => round($rows->keys()->avg(fn ($key) => $percentages->get($key)), 2),
                'graded_task_count' => $rows->pluck('task_id')->unique()->count(),
            ])->all();

        $lateCount = $submissions->unique('task_id')->filter(function ($row) {
            $submittedAt = $row->submitted_at ?? $row->created_at;

            return $submittedAt && $row->deadline
                ? Carbon::parse($submittedAt)->gt(Carbon::parse($row->deadline))
                : false;
        })->count();

        return [
            'task_avg' => $percentages->isEmpty() ? null : round($percentages->avg(), 2),
            'topic_task_performance' => $topicPerformance,
            'submission_rate' => round(($submissions->pluck('task_id')->unique()->count() / $assignedTaskCount) * 100.0, 2),
            'assigned_task_count' => $assignedTaskCount,
            'late_submission_count' => $lateCount,
        ];
    }

    private function activityFeatures(int $userId, int $labSessionId, CarbonInterface $cutoff): array
    {
        $row = DB::table('activity_logs')
            ->where('user_id', $userId)
            ->where('lab_session_id', $labSessionId)
            ->where('created_at', '<=', $cutoff)
            ->selectRaw('COUNT(*) as activity_count')
            ->first();

        $duration = app(ActivityDurationService::class)->evidenceQuery($cutoff)
            ->where('user_id', $userId)->where('lab_session_id', $labSessionId)
            ->selectRaw('SUM(duration_seconds) as seconds')->first();

        return [
            'activity_count' => (int) ($row->activity_count ?? 0),
            'activity_duration_seconds' => $duration->seconds === null ? null : (int) $duration->seconds,
        ];
    }

    private function assertStudentBelongsToClass(int $userId, int $labSessionId): void
    {
        $exists = DB::table('class_student as cs')
            ->join('users as u', 'u.id', '=', 'cs.user_id')
            ->where('cs.user_id', $userId)
            ->where('cs.lab_session_id', $labSessionId)
            ->where('u.role', 'student')
            ->exists();

        // Withdrawal must not invalidate a student's frozen historical roster.
        if (! $exists) {
            $exists = DB::table('attendance_session_students as roster')
                ->join('attendance_sessions as session', 'session.id', '=', 'roster.attendance_session_id')
                ->join('users as u', 'u.id', '=', 'roster.user_id')
                ->where('roster.user_id', $userId)
                ->where('session.lab_session_id', $labSessionId)
                ->where('u.role', 'student')
                ->exists();
        }

        if (! $exists) {
            throw new InvalidArgumentException(
                "User {$userId} has no student enrollment or historical attendance roster in lab session {$labSessionId}."
            );
        }
    }

    private function currentAcademicGrade(int $userId, int $labSessionId, CarbonInterface $cutoff): ?float
    {
        // The schema documents midterm as an interim period; final is reserved for ground truth.
        $grade = DB::table('subject_grades')
            ->where('user_id', $userId)
            ->where('lab_session_id', $labSessionId)
            ->whereRaw('LOWER(TRIM(grading_period)) = ?', ['midterm'])
            ->whereNotNull('grade')
            ->where('recorded_at', '<=', $cutoff)
            ->where('created_at', '<=', $cutoff)
            ->where('updated_at', '<=', $cutoff)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->value('grade');

        return $this->nullableFloat($grade);
    }

    private function violationCount(int $userId, int $labSessionId, CarbonInterface $cutoff): int
    {
        // Enforcement writes one activity event, plus an optional blocked_attempt for the same event.
        return DB::table('activity_logs')
            ->where('user_id', $userId)
            ->where('lab_session_id', $labSessionId)
            ->whereIn('log_type', ['violation', 'alert'])
            ->where('created_at', '<=', $cutoff)
            ->count();
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
