<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RiskCollectionStatusService
{
    public function report(?array $classIds = null, ?CarbonInterface $point = null, bool $outcomesAsOfCutoff = false): array
    {
        $cutoff = $point ? Carbon::instance($point)->startOfSecond() : now()->startOfSecond();

        return DB::transaction(function () use ($classIds, $cutoff, $outcomesAsOfCutoff) {
            $audit = app(RiskSourceDataAuditService::class)->audit($classIds, $cutoff);
            $ids = array_column($audit['classes'], 'lab_session_id');
            $pairs = DB::table('class_student as cs')->join('users as u', 'u.id', '=', 'cs.user_id')
                ->whereIn('cs.lab_session_id', $ids)->where('u.role', 'student')->where('cs.joined_at', '<=', $cutoff)
                ->select('cs.user_id', 'cs.lab_session_id')->distinct()->get();
            $observations = $pairs->map(function ($pair) use ($cutoff) {
                $features = app(StudentRiskFeatureService::class)->build((int) $pair->user_id, (int) $pair->lab_session_id, $cutoff);

                return ['user_id' => $pair->user_id, 'lab_session_id' => $pair->lab_session_id,
                    'values' => app(StudentRiskFeatureService::class)->modelInput($features),
                    'topic_quiz' => ! empty($features['additional_features']['topic_quiz_performance']),
                    'topic_task' => ! empty($features['additional_features']['topic_task_performance'])];
            });
            $materialLogs = DB::table('material_logs as ml')->join('materials as m', 'm.id', '=', 'ml.material_id')
                ->whereIn('m.lab_session_id', $ids)->where('ml.opened_at', '<=', $cutoff)
                ->get(['ml.user_id', 'm.lab_session_id', 'ml.collection_version', 'ml.measurement_mode', 'ml.duration_seconds', 'ml.updated_at']);
            // Exact cohort pairs prevent professor/non-enrolled records entering coverage.
            $pairKeys = $pairs->mapWithKeys(fn ($row) => [$row->user_id.':'.$row->lab_session_id => true]);
            $materialLogs = $materialLogs->filter(fn ($row) => isset($pairKeys[$row->user_id.':'.$row->lab_session_id]));
            $outcomes = app(OfficialOutcomeResearchService::class)->rows($ids, $outcomesAsOfCutoff ? $cutoff : null)->filter(fn ($row) => $row['recorded_at'] !== null && Carbon::parse($row['recorded_at'])->lte($cutoff));
            $outcomeKeys = $outcomes->groupBy(fn ($row) => $row['student_group_key'].':'.$row['lab_session_id']);
            $matched = $pairs->filter(fn ($row) => ($outcomeKeys->get(app(RiskFeatureSchema::class)->groupingKey((int) $row->user_id).':'.$row->lab_session_id)?->count() ?? 0) === 1);
            $sessions = DB::table('attendance_sessions')->whereIn('lab_session_id', $ids)->where('created_at', '<=', $cutoff)->get();
            $tasks = DB::table('tasks')->whereIn('subject_id', $ids)->where('created_at', '<=', $cutoff)->get(['subject_id', 'learning_topic_id']);
            try {
                $criterion = app(RiskTargetService::class)->criterion();
                $criterionStatus = $criterion ? 'CONFIGURED' : 'NOT CONFIGURED';
            } catch (InvalidArgumentException $e) {
                $criterion = null;
                $criterionStatus = 'INVALID CONFIGURATION';
            }
            $classes = [];
            foreach ($audit['classes'] as $class) {
                $classId = $class['lab_session_id'];
                $classes[] = ['lab_session_id' => $classId, 'class_code' => $class['class_code'], 'subject_name' => $class['subject_name'],
                    ...$this->scope($class['metrics'], $observations->where('lab_session_id', $classId), $materialLogs->where('lab_session_id', $classId),
                        $sessions->where('lab_session_id', $classId), $tasks->where('subject_id', $classId), $outcomes->where('lab_session_id', (string) $classId),
                        $matched->where('lab_session_id', $classId), $cutoff)];
            }
            $overall = $this->scope($audit['overall']['metrics'], $observations, $materialLogs, $sessions, $tasks, $outcomes, $matched, $cutoff);

            return ['cutoff' => $cutoff->format('Y-m-d H:i:s'), 'timezone' => config('app.timezone'), 'feature_version' => StudentRiskFeatureService::FEATURE_VERSION,
                'class_count' => count($classes), 'passing_criterion_status' => $criterionStatus, 'passing_criterion' => $criterion,
                'target_generation' => $criterion ? 'Configured; collection monitor does not generate targets' : 'DISABLED',
                'supervised_training_status' => $overall['metrics']['official_outcomes'] === 0 ? 'NOT READY / awaiting official outcomes'
                    : (! $overall['metrics']['feature_outcome_matches'] ? 'NOT READY / awaiting matched official outcomes'
                        : (! $criterion ? 'NOT READY / awaiting official passing criterion' : 'Collection available; training readiness requires separate join/timing review')),
                'overall' => $overall, 'classes' => $classes];
        });
    }

    private function scope(array $source, Collection $rows, Collection $materialLogs, Collection $sessions, Collection $tasks,
        Collection $outcomes, Collection $matched, CarbonInterface $cutoff): array
    {
        $n = $rows->count();
        $coverage = [];
        foreach ([...app(RiskFeatureSchema::class)->core(), ...app(RiskFeatureSchema::class)->optional()] as $feature) {
            $available = $rows->filter(fn ($row) => $row['values'][$feature] !== null)->count();
            $coverage[$feature] = ['available_rows' => $available, 'missing_rows' => $n - $available,
                'coverage_percent' => $n ? round(100 * $available / $n, 2) : null];
        }
        foreach (['topic_quiz', 'topic_task'] as $kind) {
            $available = $rows->where($kind, true);
            $coverage[$kind.'_performance'] = ['available_rows' => $available->count(), 'unique_students' => $available->pluck('user_id')->unique()->count(),
                'missing_rows' => $n - $available->count(), 'coverage_percent' => $n ? round(100 * $available->count() / $n, 2) : null];
        }
        $verified = $materialLogs->filter(fn ($row) => $row->collection_version === MaterialEngagementService::VERSION
            && $row->measurement_mode === 'internal_visible' && $row->duration_seconds !== null && $row->duration_seconds >= 0
            && $row->updated_at !== null && Carbon::parse($row->updated_at)->lte($cutoff));
        $verifiedPairs = $verified->unique(fn ($row) => $row->user_id.':'.$row->lab_session_id)->count();
        $coverage['verified_material_duration'] = ['available_rows' => $verifiedPairs, 'missing_rows' => $n - $verifiedPairs,
            'coverage_percent' => $n ? round(100 * $verifiedPairs / $n, 2) : null];
        $metrics = ['enrolled_students' => $rows->pluck('user_id')->unique()->count(), 'student_class_rows' => $n,
            'quizzes_total' => $source['quizzes_created_by_cutoff'], 'tagged_quizzes' => $source['quizzes_topic_tagged_by_cutoff'],
            'untagged_quizzes' => $source['quizzes_untagged_by_cutoff'], 'real_quiz_attempts' => $source['real_quiz_attempts_before_cutoff'],
            'students_with_real_quiz_attempt' => $source['students_with_real_quiz_attempts'],
            'students_with_usable_quiz_avg' => $rows->filter(fn ($row) => $row['values']['quiz_avg'] !== null)->pluck('user_id')->unique()->count(),
            'tasks_total' => $tasks->count(), 'tagged_tasks' => $tasks->whereNotNull('learning_topic_id')->count(),
            'untagged_tasks' => $tasks->whereNull('learning_topic_id')->count(), 'eligible_due_tasks' => $source['tasks_due_by_cutoff'],
            'submissions' => $source['submissions_before_cutoff'],
            'students_with_usable_task_avg' => $rows->filter(fn ($row) => $row['values']['task_avg'] !== null)->pluck('user_id')->unique()->count(),
            'material_sessions' => $source['material_sessions'], 'students_accessing_materials' => $source['students_with_material_logs'],
            'legacy_material_sessions' => $materialLogs->whereNull('collection_version')->count(),
            'legacy_material_duration_records' => $materialLogs->filter(fn ($row) => $row->collection_version === null && $row->duration_seconds !== null
                && $row->updated_at !== null && Carbon::parse($row->updated_at)->lte($cutoff))->count(),
            'new_visible_v1_material_sessions' => $materialLogs->where('collection_version', MaterialEngagementService::VERSION)->count(),
            'verified_material_duration_measurements' => $verified->count(), 'students_with_verified_material_duration' => $verified->pluck('user_id')->unique()->count(),
            'verified_activity_duration_measurements' => $source['activity_v5_verified_sessions'],
            'recorded_activity_events' => $source['activity_log_count'], 'students_with_activity_events' => $source['students_with_activity_logs'],
            'students_with_verified_activity_duration' => $source['activity_v5_students_with_duration'],
            'attendance_sessions_total' => $sessions->count(), 'attendance_sessions_active' => $sessions->where('status', 'active')->count(),
            'attendance_sessions_finalized' => $sessions->filter(fn ($session) => $session->status === 'finalized' && $session->finalized_at !== null && Carbon::parse($session->finalized_at)->lte($cutoff))->count(),
            'attendance_sessions_cancelled' => $sessions->where('status', 'cancelled')->count(),
            'students_with_usable_attendance_rate' => $rows->filter(fn ($row) => $row['values']['attendance_rate'] !== null)->pluck('user_id')->unique()->count(),
            'students_with_topic_quiz_performance' => $coverage['topic_quiz_performance']['unique_students'],
            'students_with_topic_task_performance' => $coverage['topic_task_performance']['unique_students'],
            'official_outcomes' => $outcomes->count(), 'students_with_official_outcomes' => $outcomes->pluck('student_group_key')->unique()->count(),
            'feature_outcome_matches' => $matched->count(), 'student_class_rows_awaiting_outcomes' => $n - $matched->count(),
            'students_awaiting_outcomes' => $rows->reject(fn ($row) => $matched->contains(fn ($pair) => $pair->user_id === $row['user_id'] && $pair->lab_session_id === $row['lab_session_id']))->pluck('user_id')->unique()->count()];
        $flags = [];
        $flag = function ($area, $status, $note) use (&$flags) {
            $flags[] = compact('area', 'status', 'note');
        };
        if (! $metrics['quizzes_total']) {
            $flag('Quiz', 'No data yet', 'No quizzes recorded.');
        } elseif (! $metrics['real_quiz_attempts']) {
            $flag('Quiz', 'Collecting', 'Quizzes exist; no real attempts yet.');
        } else {
            $flag('Quiz', 'Available', 'Observed real attempts; coverage does not establish enough data.');
        }
        if (! $metrics['eligible_due_tasks']) {
            $flag('Tasks', 'Not applicable', 'No eligible/due tasks at this cutoff.');
        } else {
            $flag('Tasks', 'Collecting', 'Due-task opportunities exist; inspect usable grade coverage.');
        }
        if (($metrics['quizzes_total'] && ! $metrics['tagged_quizzes']) || ($metrics['tasks_total'] && ! $metrics['tagged_tasks'])) {
            $flag('Topics', 'Collecting', 'All recorded quizzes and/or tasks in this scope are untagged.');
        }
        if (! $metrics['attendance_sessions_finalized']) {
            $flag('Attendance', 'No data yet', 'No cutoff-eligible finalized verified sessions.');
        }
        if (! $metrics['verified_activity_duration_measurements']) {
            $flag('System duration', 'No data yet', 'No verified UI-active duration measurements.');
        }
        if ($coverage['reading_avg_seconds']['missing_rows']) {
            $flag('Reading duration', 'Collecting', 'Reading duration is missing for '.$coverage['reading_avg_seconds']['missing_rows'].' student-class rows; no sufficiency threshold is applied.');
        }
        if (! $verifiedPairs) {
            $flag('Verified material duration', 'No data yet', 'No bounded visible-viewing duration evidence; legacy duration is separate.');
        }
        if (! $metrics['official_outcomes']) {
            $flag('Official outcomes', 'No data yet', 'Awaiting professor/institutional final records.');
        }
        if (! $n) {
            $flag('Enrollment', 'Not applicable', 'No student enrollment by cutoff.');
        }

        return ['metrics' => $metrics, 'coverage' => $coverage, 'flags' => $flags];
    }

    public function export(array $report, string $filename): string
    {
        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.csv\z/', $filename)) {
            throw new InvalidArgumentException('--output must be a CSV filename without directories.');
        }
        $path = storage_path('app/risk-datasets/'.$filename);
        if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true)) {
            throw new RuntimeException('Cannot create collection report directory.');
        }
        $stream = @fopen($path, 'x');
        if (! $stream) {
            throw new RuntimeException('Choose a new output filename; existing reports are never overwritten.');
        }
        try {
            $this->writeCsv($report, $stream);
        } catch (\Throwable $e) {
            fclose($stream);
            unlink($path);
            throw $e;
        }
        fclose($stream);

        return $path;
    }

    /** Write the unchanged collection-report schema to a caller-owned stream. */
    public function writeCsv(array $report, $stream): void
    {
        $write = function ($row) use ($stream) {
            if (fputcsv($stream, $row, ',', '"', '') === false) {
                throw new RuntimeException('Cannot write collection report CSV.');
            }
        };
        $write(['scope', 'lab_session_id', 'class_code', 'subject_name', 'cutoff', 'feature_version', 'category', 'measure', 'value']);
        $global = ['passing_criterion_status' => $report['passing_criterion_status'], 'passing_grade' => $report['passing_criterion']['passing_grade'] ?? null,
            'passing_operator' => $report['passing_criterion']['passing_operator'] ?? null, 'target_generation' => $report['target_generation'], 'supervised_training_status' => $report['supervised_training_status']];
        foreach ([['lab_session_id' => null, 'class_code' => null, 'subject_name' => null, ...$report['overall']], ...$report['classes']] as $scope) {
            $base = [$scope['lab_session_id'] === null ? 'overall' : 'class', $scope['lab_session_id'], $this->safe($scope['class_code']), $this->safe($scope['subject_name']), $report['cutoff'], $report['feature_version']];
            foreach ($scope['metrics'] as $measure => $value) {
                $write([...$base, 'observation', $measure, $value]);
            }
            foreach ($scope['coverage'] as $feature => $coverage) {
                foreach ($coverage as $stat => $value) {
                    $write([...$base, 'coverage', $feature.'.'.$stat, $value]);
                }
            }
            foreach ($scope['flags'] as $flag) {
                $write([...$base, 'collection_status', $flag['area'], $flag['status'].': '.$flag['note']]);
            }
            if ($scope['lab_session_id'] === null) {
                foreach ($global as $measure => $value) {
                    $write([...$base, 'readiness', $measure, $value]);
                }
            }
        }
        if (! fflush($stream)) {
            throw new RuntimeException('Cannot flush collection report CSV.');
        }
    }

    private function safe(?string $value): ?string
    {
        return $value !== null && preg_match('/\A\s*[=+@-]/', $value) ? "'".$value : $value;
    }
}
