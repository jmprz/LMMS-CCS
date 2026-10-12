<?php

namespace Tests\Feature;

use App\Services\RiskSourceDataAuditService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RiskSourceDataAuditTest extends TestCase
{
    private Carbon $cutoff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $tables = [
            'users' => ['role'], 'lab_sessions' => ['class_code', 'subject_name', 'is_active'],
            'class_student' => ['user_id', 'lab_session_id', 'joined_at', 'violation_count'],
            'quizzes' => ['subject_id', 'published_at', 'learning_topic_id'],
            'quiz_attempts' => ['user_id', 'quiz_id', 'score', 'total_points', 'is_simulated'],
            'learning_topics' => ['name'], 'tasks' => ['subject_id', 'deadline', 'points'],
            'submissions' => ['user_id', 'task_id', 'grade', 'submitted_at', 'duration_seconds'],
            'submission_grades' => ['submission_id', 'total_score', 'max_score'],
            'attendances' => ['user_id', 'lab_session_id', 'attendance_date', 'status'],
            'materials' => ['lab_session_id', 'type'], 'material_logs' => ['user_id', 'material_id', 'opened_at', 'closed_at', 'duration_seconds', 'collection_version', 'measurement_mode', 'last_heartbeat_at'],
            'activity_logs' => ['user_id', 'lab_session_id', 'log_type', 'duration_seconds', 'ended_at'],
            'blocked_attempts' => ['user_id', 'lab_session_id', 'attempted_at'],
            'subject_grades' => ['user_id', 'lab_session_id', 'grading_period', 'grade', 'recorded_at'],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    if (str_ends_with($column, '_id') || in_array($column, ['is_active', 'is_simulated', 'violation_count', 'duration_seconds'])) {
                        $table->integer($column)->nullable();
                    } elseif (in_array($column, ['grade', 'score', 'points', 'total_points', 'total_score', 'max_score'])) {
                        $table->double($column)->nullable();
                    } else {
                        $table->string($column)->nullable();
                    }
                }
                $table->timestamps();
            });
        }
        $this->cutoff = Carbon::parse('2026-10-01 23:59:59');
        (require database_path('migrations/2026_10_03_000001_create_activity_duration_sessions_table.php'))->up();
        foreach ([1 => true, 2 => false] as $id => $active) {
            $this->row('lab_sessions', ['id' => $id, 'class_code' => 'CLASS-'.$id, 'subject_name' => 'Programming', 'is_active' => $active]);
        }
        foreach ([900001, 900002, 900003] as $id) {
            $this->row('users', ['id' => $id, 'role' => 'student']);
            $this->row('class_student', ['user_id' => $id, 'lab_session_id' => 1, 'violation_count' => 0,
                'joined_at' => $id === 900003 ? '2026-10-02 09:00:00' : '2026-09-01 09:00:00']);
        }
        $this->row('class_student', ['user_id' => 900001, 'lab_session_id' => 1, 'joined_at' => '2026-09-01 09:00:00', 'violation_count' => 0]);
        $this->row('class_student', ['user_id' => 900001, 'lab_session_id' => 2, 'joined_at' => '2026-09-01 09:00:00', 'violation_count' => 0]);
    }

    private function row(string $table, array $row): int
    {
        return DB::table($table)->insertGetId(['created_at' => '2026-09-01 09:00:00', 'updated_at' => '2026-09-01 09:00:00', ...$row]);
    }

    public function test_verified_activity_audit_has_cutoff_safe_duration_and_class_coverage(): void
    {
        $this->row('activity_logs', ['user_id' => 900001, 'lab_session_id' => 1, 'log_type' => 'submission', 'duration_seconds' => 999999]);
        foreach ([[10, '2026-09-01 09:00:00'], [20, '2026-09-01 09:00:00'], [900, '2026-10-02 09:00:00']] as [$seconds, $updated]) {
            $this->row('activity_duration_sessions', ['user_id' => 900001, 'lab_session_id' => 1,
                'client_session_key' => (string) \Illuminate\Support\Str::uuid(), 'collection_version' => 'generic_ui_v1',
                'opened_at' => '2026-09-01 09:00:00', 'last_heartbeat_at' => '2026-09-01 09:00:00',
                'duration_seconds' => $seconds, 'updated_at' => $updated]);
        }
        $report = app(RiskSourceDataAuditService::class)->audit(null, $this->cutoff);
        $metrics = $report['overall']['metrics'];
        $this->assertSame(1, $metrics['activity_logs_excluded_from_v5_duration']);
        $this->assertSame(2, $metrics['activity_v5_verified_sessions']);
        $this->assertSame(1, $metrics['activity_v5_students_with_duration']);
        $this->assertEquals(30, $metrics['activity_v5_student_class_total_mean']);
        $this->assertEquals(15, $metrics['activity_v5_valid_session_duration_median']);
        $this->assertEquals(50, $report['classes'][0]['metrics']['activity_v5_coverage_percent']);
        $this->assertSame(1, $metrics['activity_v5_student_class_rows_null_duration']);
    }

    public function test_quiz_source_coverage_excludes_simulations_and_explains_invalid_and_future_attempts(): void
    {
        $this->row('learning_topics', ['id' => 7, 'name' => 'Loops']);
        foreach ([1 => 7, 2 => null] as $id => $topic) {
            $this->row('quizzes', ['id' => $id, 'subject_id' => 1, 'learning_topic_id' => $topic, 'published_at' => '2026-09-10 09:00:00']);
        }
        foreach ([
            [900001, 1, 8, 10, false, '2026-09-10 09:00:00'],
            [900002, 2, 8, 0, false, '2026-09-10 09:00:00'],
            [900002, 2, 10, 10, true, '2026-09-10 09:00:00'],
            [900002, 2, 10, 10, false, '2026-10-02 09:00:00'],
            [900003, 1, 10, 10, false, '2026-09-10 09:00:00'],
        ] as [$user, $quiz, $score, $points, $simulated, $created]) {
            $this->row('quiz_attempts', ['user_id' => $user, 'quiz_id' => $quiz, 'score' => $score, 'total_points' => $points,
                'is_simulated' => $simulated, 'created_at' => $created]);
        }
        $result = app(RiskSourceDataAuditService::class)->audit(null, $this->cutoff);
        $metrics = $result['classes'][0]['metrics'];
        $this->assertSame(1, $result['class_count']);
        $this->assertSame(2, $metrics['enrolled_student_class_pairs']);
        $this->assertSame(2, $metrics['real_quiz_attempts_before_cutoff']);
        $this->assertSame(2, $metrics['students_with_real_quiz_attempts']);
        $this->assertSame(1, $metrics['students_with_usable_quiz_percentages']);
        $this->assertSame(1, $metrics['real_attempts_unusable_by_v2']);
        $this->assertSame(1, $metrics['quizzes_topic_tagged_by_cutoff']);
        $this->assertSame(1, $result['classes'][0]['topics'][0]['real_attempts']);
        $this->assertSame('Loops', $result['classes'][0]['topics'][0]['topic_name']);
        $this->assertStringNotContainsString('900001', json_encode($result));
    }

    public function test_due_tasks_rubric_fallback_and_ungraded_sources_are_distinguished(): void
    {
        foreach ([1 => '2026-09-20 09:00:00', 2 => '2026-10-10 09:00:00'] as $id => $deadline) {
            $this->row('tasks', ['id' => $id, 'subject_id' => 1, 'deadline' => $deadline, 'points' => 10]);
        }
        foreach ([[1, 900001, 1, 8], [2, 900002, 1, 7], [3, 900001, 1, null], [4, 900001, 2, 8]] as [$id, $user, $task, $grade]) {
            $this->row('submissions', ['id' => $id, 'user_id' => $user, 'task_id' => $task, 'grade' => $grade,
                'submitted_at' => '2026-09-20 10:00:00', 'duration_seconds' => 1645200]);
        }
        $this->row('submission_grades', ['submission_id' => 1, 'total_score' => 9, 'max_score' => 10]);
        $this->row('submission_grades', ['submission_id' => 2, 'total_score' => 9, 'max_score' => 10, 'updated_at' => '2026-10-02 09:00:00']);
        $metrics = app(RiskSourceDataAuditService::class)->audit([1], $this->cutoff)['overall']['metrics'];
        $this->assertSame(2, $metrics['tasks_total_current']);
        $this->assertSame(1, $metrics['tasks_due_by_cutoff']);
        $this->assertSame(4, $metrics['submissions_before_cutoff']);
        $this->assertSame(4, $metrics['submission_durations_equal_task_age']);
        $this->assertSame(3, $metrics['eligible_submission_rows']);
        $this->assertSame(2, $metrics['latest_eligible_submissions']);
        $this->assertSame(2, $metrics['eligible_submissions_with_rubric_records']);
        $this->assertSame(1, $metrics['eligible_submissions_using_rubric_score']);
        $this->assertSame(1, $metrics['eligible_submissions_using_fallback_grade']);
        $this->assertSame(1, $metrics['eligible_ungraded_submissions']);
        $this->assertSame(1, $metrics['students_with_usable_latest_task_percentages']);
        $this->assertSame(0, $metrics['topic_task_supported']);
    }

    public function test_material_audit_distinguishes_legacy_visible_external_null_and_zero_sessions(): void
    {
        $this->row('materials', ['id' => 1, 'lab_session_id' => 1, 'type' => 'pdf']);
        foreach ([
            [null, null, null, null],
            ['visible_v1', 'internal_visible', 0, null],
            ['visible_v1', 'internal_visible', 10, '2026-09-10 10:00:00'],
            ['visible_v1', 'external_open', null, '2026-09-10 10:00:00'],
        ] as [$version, $mode, $duration, $closed]) {
            $this->row('material_logs', ['user_id' => 900001, 'material_id' => 1, 'opened_at' => '2026-09-10 09:00:00',
                'duration_seconds' => $duration, 'collection_version' => $version, 'measurement_mode' => $mode, 'closed_at' => $closed]);
        }
        $report = app(RiskSourceDataAuditService::class)->audit([1], $this->cutoff);
        $m = $report['overall']['metrics'];
        $this->assertSame(4, $m['material_sessions']);
        $this->assertSame(2, $m['material_logs_closed']);
        $this->assertSame(2, $m['material_logs_unclosed']);
        $this->assertSame(2, $m['material_logs_null_duration']);
        $this->assertSame(1, $m['material_logs_zero_duration']);
        $this->assertEquals(50, $m['material_duration_coverage_percent']);
        $this->assertSame(1, $m['material_logs_legacy_collection']);
        $this->assertSame(3, $m['material_logs_visible_v1_collection']);
        $this->assertSame(1, $m['material_logs_external_open']);
        $this->assertSame(1, $m['material_duplicate_open_timestamp_groups']);
        $this->assertSame(1, $m['material_multiple_unclosed_pair_groups']);
        $this->assertSame(2, $m['material_type_pdf_null_duration']);
        $this->assertStringNotContainsString('900001', json_encode($report));
    }

    public function test_duration_stats_cutoff_guards_grade_periods_and_violation_sources(): void
    {
        $this->row('materials', ['id' => 1, 'lab_session_id' => 1]);
        foreach ([null, 10, 30] as $duration) {
            $this->row('material_logs', ['user_id' => 900001, 'material_id' => 1, 'opened_at' => '2026-09-10 09:00:00', 'duration_seconds' => $duration]);
        }
        foreach ([10, 30, 100000] as $duration) {
            $this->row('activity_logs', ['user_id' => 900001, 'lab_session_id' => 1, 'log_type' => 'navigation', 'duration_seconds' => $duration]);
        }
        $this->row('activity_logs', ['user_id' => 900002, 'lab_session_id' => 1, 'log_type' => 'violation', 'duration_seconds' => 100,
            'updated_at' => '2026-10-02 09:00:00']);
        $this->row('blocked_attempts', ['user_id' => 900001, 'lab_session_id' => 1, 'attempted_at' => '2026-09-10 09:00:00']);
        foreach (['prelim', 'midterm', 'final'] as $period) {
            $this->row('subject_grades', ['user_id' => 900001, 'lab_session_id' => 1, 'grading_period' => $period, 'grade' => 2,
                'recorded_at' => $period === 'final' ? '2026-10-02 09:00:00' : '2026-09-10 09:00:00']);
        }
        $this->row('attendances', ['user_id' => 900001, 'lab_session_id' => 1, 'attendance_date' => '2026-09-10', 'status' => 'present']);
        $report = app(RiskSourceDataAuditService::class)->audit([1, 2, 2], $this->cutoff);
        $m = $report['overall']['metrics'];
        $this->assertSame(2, $report['class_count']);
        $this->assertSame(3, $m['enrolled_student_class_pairs']);
        $this->assertSame(2, $m['unique_students']);
        $this->assertSame(3, $m['material_sessions']);
        $this->assertSame(1, $m['material_logs_null_duration']);
        $this->assertEquals(20, $m['material_duration_median']);
        $this->assertEquals(65, $m['activity_duration_median']);
        $this->assertEquals(100000, $m['activity_duration_max']);
        $this->assertSame(1, $m['activity_duration_over_24h']);
        $this->assertSame(1, $m['activity_logs_updated_after_cutoff']);
        $this->assertEquals(0, $m['activity_student_class_total_median']);
        $this->assertSame(1, $m['violation_activity_events']);
        $this->assertSame(1, $m['blocked_attempts_before_cutoff']);
        $this->assertSame(2, $m['students_with_violation_or_blocked_attempt']);
        $this->assertSame(0, $m['violation_feature_zero_variance']);
        $this->assertSame(3, $m['subject_grade_records_current']);
        $this->assertSame(2, $m['subject_grade_records_before_cutoff']);
        $this->assertSame(1, $m['subject_grade_records_after_cutoff']);
        $this->assertSame(1, $m['students_with_current_academic_grade_source']);
        $this->assertSame(['prelim', 'midterm', 'final'], array_column($report['overall']['grading_periods'], 'grading_period'));
        $this->assertSame(['present' => 1], $report['overall']['attendance_statuses']);
    }

    public function test_command_default_cutoff_validation_privacy_and_csv_output(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 23:59:59'));
        $filename = 'test-source-audit-'.bin2hex(random_bytes(6)).'.csv';
        $path = storage_path('app/risk-datasets/'.$filename);
        try {
            $this->artisan('risk:data-audit', ['--output' => $filename])
                ->expectsOutputToContain('Topic scalar encoding is pending; this audit does not define topic task predictors.')
                ->doesntExpectOutputToContain('900001')->assertSuccessful();
            $this->assertStringNotContainsString('900001', file_get_contents($path));
            $this->assertStringContainsString('2026-10-01 23:59:59', file_get_contents($path));
            $this->artisan('risk:data-audit', ['--output' => $filename])->assertFailed();
            $this->artisan('risk:data-audit', ['--classes' => '1,no'])->assertFailed();
            $this->artisan('risk:data-audit', ['--classes' => '999'])->assertFailed();
            $this->artisan('risk:data-audit', ['--cutoff' => '2026-02-30 23:59:59'])->assertFailed();
            $this->artisan('risk:data-audit', ['--cutoff' => '2026-10-02 23:59:59'])->assertFailed();
            $this->artisan('risk:data-audit', ['--output' => '../bad.csv'])->assertFailed();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            $this->travelBack();
        }
    }
}
