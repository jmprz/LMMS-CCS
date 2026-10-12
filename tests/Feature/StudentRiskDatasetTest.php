<?php

namespace Tests\Feature;

use App\Services\StudentRiskDatasetService;
use App\Services\StudentRiskFeatureService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentRiskDatasetTest extends TestCase
{
    private Carbon $cutoff;

    protected function setUp(): void
    {
        parent::setUp();
        // Isolated SQLite fixtures using verified live column names. No production writes.
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $tables = [
            'users' => ['role'],
            'learning_topics' => ['name'],
            'lab_sessions' => ['class_code', 'subject_name', 'program', 'year_level', 'section', 'is_active'],
            'class_student' => ['user_id', 'lab_session_id', 'joined_at'],
            'quizzes' => ['subject_id', 'learning_topic_id'],
            'quiz_attempts' => ['user_id', 'quiz_id', 'score', 'total_points', 'is_simulated'],
            'materials' => ['lab_session_id'],
            'material_logs' => ['user_id', 'material_id', 'opened_at', 'duration_seconds'],
            'attendances' => ['user_id', 'lab_session_id', 'attendance_date', 'status'],
            'tasks' => ['subject_id', 'deadline', 'points'],
            'submissions' => ['user_id', 'task_id', 'grade', 'submitted_at'],
            'submission_grades' => ['submission_id', 'total_score', 'max_score'],
            'subject_grades' => ['user_id', 'lab_session_id', 'grading_period', 'grade', 'recorded_at'],
            'activity_logs' => ['user_id', 'lab_session_id', 'log_type', 'duration_seconds'],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    if (str_ends_with($column, '_id') || in_array($column, ['is_simulated', 'is_active', 'duration_seconds'])) {
                        $table->integer($column)->nullable();
                    } elseif (in_array($column, ['grade', 'score', 'total_points', 'points', 'total_score', 'max_score'])) {
                        $table->double($column)->nullable();
                    } else {
                        $table->string($column)->nullable();
                    }
                }
                $table->timestamps();
            });
        }
        (require database_path('migrations/2026_10_02_000001_create_attendance_sessions_table.php'))->up();
        (require database_path('migrations/2026_10_03_000001_create_activity_duration_sessions_table.php'))->up();
        (require database_path('migrations/2026_10_02_000003_add_learning_topic_to_tasks_table.php'))->up();
        (require database_path('migrations/2026_10_02_000002_create_attendance_session_students_table.php'))->up();
        (require database_path('migrations/2026_09_26_18007_create_student_feature_snapshots_table.php'))->up();
        $this->cutoff = Carbon::parse('2026-09-30 17:00:00');
        foreach ([7 => 'Programming', 8 => 'Automata'] as $id => $name) {
            $this->insert('learning_topics', ['id' => $id, 'name' => $name]);
        }
        $this->insert('lab_sessions', [
            'id' => 1, 'class_code' => 'CCS-1', 'subject_name' => 'Programming',
            'program' => 'BSCS', 'year_level' => '2', 'section' => 'A', 'is_active' => true,
        ]);
        foreach ([391, 392, 393] as $id) {
            $this->insert('users', ['id' => $id, 'role' => 'student']);
            $this->insert('class_student', [
                'user_id' => $id, 'lab_session_id' => 1,
                'joined_at' => $id === 393 ? '2026-10-01 09:00:00' : '2026-09-01 09:00:00',
            ]);
        }
    }

    private function insert(string $table, array $values): int
    {
        return DB::table($table)->insertGetId([
            'created_at' => '2026-09-01 09:00:00',
            'updated_at' => '2026-09-01 09:00:00',
            ...$values,
        ]);
    }

    public function test_activity_events_remain_countable_but_historical_durations_are_never_used(): void
    {
        foreach (['submission', 'quiz', 'material', 'navigation', 'attendance'] as $type) {
            $this->insert('activity_logs', ['user_id' => 391, 'lab_session_id' => 1, 'log_type' => $type, 'duration_seconds' => 999999]);
        }
        $this->insert('activity_logs', ['user_id' => 391, 'lab_session_id' => 1, 'log_type' => 'submission', 'created_at' => '2026-10-01 00:00:00']);
        $features = app(StudentRiskFeatureService::class)->build(391, 1, $this->cutoff)['additional_features'];
        $this->assertSame(5, $features['activity_count']);
        $this->assertNull($features['activity_duration_seconds']);
        $this->assertSame(999999, DB::table('activity_logs')->value('duration_seconds'));
        $empty = app(StudentRiskFeatureService::class)->build(392, 1, $this->cutoff)['additional_features'];
        $this->assertSame(0, $empty['activity_count']);
        $this->assertNull($empty['activity_duration_seconds']);
    }

    public function test_verified_activity_duration_sums_only_evidence_available_at_cutoff(): void
    {
        foreach ([
            [20, 'generic_ui_v1', '2026-09-01 09:00:00', '2026-09-01 09:00:00'],
            [30, 'generic_ui_v1', '2026-09-01 09:00:00', '2026-09-01 09:00:00'],
            [null, 'generic_ui_v1', '2026-09-01 09:00:00', '2026-09-01 09:00:00'],
            [900, 'unverified', '2026-09-01 09:00:00', '2026-09-01 09:00:00'],
            [900, 'generic_ui_v1', '2026-10-01 09:00:00', '2026-10-01 09:00:00'],
            [900, 'generic_ui_v1', '2026-09-01 09:00:00', '2026-10-01 09:00:00'],
        ] as [$duration, $version, $opened, $updated]) {
            $this->insert('activity_duration_sessions', ['user_id' => 391, 'lab_session_id' => 1,
                'client_session_key' => (string) \Illuminate\Support\Str::uuid(), 'collection_version' => $version,
                'duration_seconds' => $duration, 'opened_at' => $opened, 'last_heartbeat_at' => $opened, 'updated_at' => $updated]);
        }
        $this->assertSame(50, app(StudentRiskFeatureService::class)->build(391, 1, $this->cutoff)['additional_features']['activity_duration_seconds']);
        DB::table('activity_duration_sessions')->where('duration_seconds', '<', 900)->update(['duration_seconds' => 0]);
        $this->assertSame(0, app(StudentRiskFeatureService::class)->build(391, 1, $this->cutoff)['additional_features']['activity_duration_seconds']);
    }

    public function test_predictors_exclude_final_grades_future_data_and_simulated_attempts(): void
    {
        foreach ([10 => 7, 11 => 7, 12 => 8, 13 => null] as $quizId => $topicId) {
            $this->insert('quizzes', ['id' => $quizId, 'subject_id' => 1, 'learning_topic_id' => $topicId]);
        }
        foreach ([
            [10, 20, 100, false, '2026-09-02 09:00:00'],
            [10, 80, 100, false, '2026-09-03 09:00:00'],
            [10, 100, 100, true, '2026-09-04 09:00:00'],
            [10, 100, 0, false, '2026-09-05 09:00:00'],
            [11, 60, 100, false, '2026-09-04 09:00:00'],
            [12, 100, 100, false, '2026-10-01 09:00:00'],
            [13, 40, 100, false, '2026-09-04 09:00:00'],
        ] as [$quizId, $score, $points, $simulated, $time]) {
            $this->insert('quiz_attempts', [
                'user_id' => 391, 'quiz_id' => $quizId, 'score' => $score,
                'total_points' => $points, 'is_simulated' => $simulated,
                'created_at' => $time, 'updated_at' => $time,
            ]);
        }
        foreach ([['midterm', 2.25], ['final', 5.0]] as [$period, $grade]) {
            $this->insert('subject_grades', [
                'user_id' => 391, 'lab_session_id' => 1, 'grading_period' => $period,
                'grade' => $grade, 'recorded_at' => '2026-09-10 09:00:00',
            ]);
        }
        foreach (['violation', 'alert', 'navigation'] as $type) {
            $this->insert('activity_logs', ['user_id' => 391, 'lab_session_id' => 1, 'log_type' => $type, 'duration_seconds' => 10]);
        }
        $this->insert('activity_logs', [
            'user_id' => 391, 'lab_session_id' => 1, 'log_type' => 'violation',
            'created_at' => '2026-10-01 09:00:00',
        ]);
        $service = app(StudentRiskFeatureService::class);
        $features = $service->build(391, 1, $this->cutoff);
        $this->assertEquals(60, $features['quiz_avg']);
        $this->assertSame(3, $features['quiz_attempt_count']);
        $this->assertEquals([7 => 70], $features['additional_features']['topic_quiz_average']);
        $this->assertEquals([7 => ['topic_name' => 'Programming', 'average' => 70, 'attempt_count' => 2]], $features['additional_features']['topic_quiz_performance']);
        $this->assertEquals(2.25, $features['additional_features']['current_academic_grade']);
        $this->assertSame(2, $features['additional_features']['violation_count']);
        $this->assertSame(3, $features['additional_features']['activity_count']);
        $this->assertNull($features['additional_features']['activity_duration_seconds']);
        $this->assertNull($features['additional_features']['topic_task_average']);

        DB::table('subject_grades')->where('grading_period', 'midterm')->update(['updated_at' => '2026-10-01 09:00:00']);
        $this->assertNull($service->build(391, 1, $this->cutoff)['additional_features']['current_academic_grade']);
    }

    public function test_existing_averages_rates_and_cutoff_guards(): void
    {
        foreach ([1 => '2026-09-10 12:00:00', 2 => '2026-09-10 12:00:00', 3 => '2026-10-10 12:00:00'] as $id => $deadline) {
            $this->insert('tasks', ['id' => $id, 'subject_id' => 1, 'points' => 20, 'deadline' => $deadline]);
        }
        $this->insert('submissions', ['id' => 1, 'user_id' => 391, 'task_id' => 1, 'grade' => 10, 'submitted_at' => '2026-09-11 09:00:00']);
        $this->insert('submission_grades', ['submission_id' => 1, 'total_score' => 16, 'max_score' => 20]);
        $this->insert('submissions', ['user_id' => 391, 'task_id' => 2, 'grade' => 20, 'submitted_at' => '2026-10-01 09:00:00']);
        $this->insert('materials', ['id' => 1, 'lab_session_id' => 1]);
        foreach ([20, 40] as $seconds) {
            $this->insert('material_logs', ['user_id' => 391, 'material_id' => 1, 'opened_at' => '2026-09-05 09:00:00', 'duration_seconds' => $seconds]);
        }
        $this->insert('material_logs', [
            'user_id' => 391, 'material_id' => 1, 'opened_at' => '2026-09-30 16:59:00',
            'duration_seconds' => 600, 'updated_at' => '2026-09-30 17:10:00',
        ]);
        $this->insert('attendances', ['user_id' => 391, 'lab_session_id' => 1, 'attendance_date' => '2026-09-05', 'status' => 'present']);
        $this->insert('attendances', ['user_id' => 392, 'lab_session_id' => 1, 'attendance_date' => '2026-09-06', 'status' => 'present']);
        $service = app(StudentRiskFeatureService::class);
        $features = $service->build(391, 1, $this->cutoff);
        $this->assertEquals(80, $features['task_avg']);
        $this->assertEquals(50, $features['submission_rate']);
        $this->assertSame(1, $features['additional_features']['late_submission_count']);
        $this->assertSame(2, $features['assigned_task_count']);
        $this->assertEquals(30, $features['reading_avg_seconds']);
        $this->assertSame(60, $features['reading_total_seconds']);
        $this->assertSame(3, $features['material_session_count']);
        $this->assertSame(1, $features['materials_accessed']);
        $this->assertNull($features['attendance_rate']);
        $this->assertSame(0, $features['eligible_session_count']);
        DB::table('submission_grades')->update(['updated_at' => '2026-10-01 09:00:00']);
        $this->assertEquals(50, $service->build(391, 1, $this->cutoff)['task_avg']);
        DB::table('submissions')->where('id', 1)->update(['updated_at' => '2026-10-01 09:00:00']);
        $this->assertNull($service->build(391, 1, $this->cutoff)['task_avg']);
    }

    public function test_csv_has_shared_cutoff_stable_topic_columns_nulls_and_idempotent_snapshots(): void
    {
        $this->insert('quizzes', ['id' => 10, 'subject_id' => 1, 'learning_topic_id' => 7]);
        $this->insert('quiz_attempts', ['user_id' => 391, 'quiz_id' => 10, 'score' => 8, 'total_points' => 10, 'is_simulated' => false]);
        // Duplicate membership should not produce a duplicate dataset row.
        $this->insert('class_student', ['user_id' => 391, 'lab_session_id' => 1, 'joined_at' => '2026-09-01 09:00:00']);
        $datasets = app(StudentRiskDatasetService::class);
        for ($run = 0; $run < 2; $run++) {
            $stream = fopen('php://temp', 'w+');
            $this->assertSame(2, $datasets->exportCsv(1, $this->cutoff, $stream, true));
            rewind($stream);
            $header = fgetcsv($stream, escape: '');
            $first = array_combine($header, fgetcsv($stream, escape: ''));
            $second = array_combine($header, fgetcsv($stream, escape: ''));
            $this->assertFalse(fgetcsv($stream, escape: ''));
            fclose($stream);
            $this->assertSame('2026-09-30 17:00:00', $first['feature_cutoff_at']);
            $this->assertSame($first['feature_cutoff_at'], $second['feature_cutoff_at']);
            $this->assertEquals(80, json_decode($first['topic_quiz_performance_json'], true)[7]['average']);
            $this->assertSame('{}', $second['topic_quiz_performance_json']);
            $this->assertSame('', $second['quiz_avg']);
            $this->assertSame('', $second['current_academic_grade']);
            $this->assertSame('', $first['topic_task_average']);
            $this->assertNotContains('final_academic_outcome', $header);
            $this->assertNotContains('academic_risk_status', $header);
            $this->assertSame(2, DB::table('student_feature_snapshots')->count());
            DB::table('quiz_attempts')->update(['score' => 1]);
        }
    }

    public function test_read_only_export_does_not_save_and_failed_bulk_save_rolls_back(): void
    {
        $stream = fopen('php://temp', 'w+');
        $datasets = app(StudentRiskDatasetService::class);
        $this->assertSame(2, $datasets->exportCsv(1, $this->cutoff, $stream));
        $this->assertSame(0, DB::table('student_feature_snapshots')->count());
        fclose($stream);

        $first = app(StudentRiskFeatureService::class)->build(391, 1, $this->cutoff);
        $features = \Mockery::mock(StudentRiskFeatureService::class)->makePartial();
        $features->shouldReceive('build')->with(391, 1, \Mockery::any())->once()->andReturn($first);
        $features->shouldReceive('build')->with(392, 1, \Mockery::any())->once()
            ->andThrow(new \RuntimeException('Second student failed'));
        $stream = fopen('php://temp', 'w+');
        try {
            (new StudentRiskDatasetService($features))->exportCsv(1, $this->cutoff, $stream, true);
            $this->fail('Expected bulk snapshot failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Second student failed', $e->getMessage());
            $this->assertSame(0, DB::table('student_feature_snapshots')->count());
        } finally {
            fclose($stream);
        }
    }

    public function test_export_command_requires_cutoff_and_rejects_invalid_dates(): void
    {
        $this->artisan('risk:export-class', ['lab_session_id' => 1])->assertFailed();
        $this->artisan('risk:export-class', ['lab_session_id' => 1, '--cutoff' => '2026-02-30 17:00:00'])->assertFailed();
    }

    public function test_topic_task_performance_reuses_latest_eligible_grades_and_ignores_untagged_and_future_tasks(): void
    {
        foreach ([1 => 7, 2 => 7, 3 => null, 4 => 8, 5 => 7] as $taskId => $topicId) {
            $this->insert('tasks', ['id' => $taskId, 'subject_id' => 1, 'learning_topic_id' => $topicId,
                'deadline' => $taskId === 5 ? '2026-10-01 09:00:00' : '2026-09-10 09:00:00', 'points' => 20]);
            $this->insert('submissions', ['id' => $taskId, 'user_id' => 391, 'task_id' => $taskId,
                'grade' => $taskId === 4 ? null : 10, 'submitted_at' => '2026-09-11 09:00:00']);
        }
        $this->insert('submissions', ['id' => 6, 'user_id' => 391, 'task_id' => 1, 'grade' => 2, 'submitted_at' => '2026-09-12 09:00:00']);
        $this->insert('submission_grades', ['submission_id' => 6, 'total_score' => 16, 'max_score' => 20]);
        $this->insert('submissions', ['user_id' => 391, 'task_id' => 1, 'grade' => 20, 'submitted_at' => '2026-10-01 09:00:00']);
        $this->insert('submission_grades', ['submission_id' => 2, 'total_score' => 20, 'max_score' => 20, 'updated_at' => '2026-10-01 09:00:00']);
        $features = app(StudentRiskFeatureService::class)->build(391, 1, $this->cutoff);
        $this->assertEquals([7 => ['topic_name' => 'Programming', 'average' => 65, 'graded_task_count' => 2]], $features['additional_features']['topic_task_performance']);
        $this->assertEquals(60, $features['task_avg']);
        $this->assertNull($features['additional_features']['topic_task_average']);
        $stream = fopen('php://temp', 'w+');
        try {
            app(StudentRiskDatasetService::class)->exportCsv(1, $this->cutoff, $stream);
            [, $rows] = $this->readCsv($stream);
            $this->assertEquals(65, json_decode($rows[0]['topic_task_performance_json'], true)[7]['average']);
            $this->assertSame('4', $rows[0]['tagged_task_count']);
            $this->assertSame('1', $rows[0]['untagged_task_count']);
        } finally {
            fclose($stream);
        }
    }

    private function attendanceSession(array $roster = [391 => 'present'], string $status = 'finalized', ?string $finalizedAt = '2026-09-30 17:00:00', int $classId = 1): int
    {
        DB::table('users')->insertOrIgnore(['id' => 500, 'role' => 'professor']);
        $sessionId = $this->insert('attendance_sessions', [
            'lab_session_id' => $classId, 'created_by' => 500, 'session_date' => '2026-09-30',
            'started_at' => '2026-09-30 09:00:00', 'ended_at' => $finalizedAt,
            'status' => $status, 'finalized_at' => $finalizedAt,
        ]);
        foreach ($roster as $userId => $attendanceStatus) {
            $this->insert('attendance_session_students', [
                'attendance_session_id' => $sessionId, 'user_id' => $userId, 'status' => $attendanceStatus,
            ]);
        }

        return $sessionId;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('verifiedAttendanceStatuses')]
    public function test_verified_attendance_status_denominators(string $status, ?float $rate, int $count): void
    {
        $this->attendanceSession([391 => $status]);
        $features = app(StudentRiskFeatureService::class)->build(391, 1, $this->cutoff);
        $this->assertSame($rate, $features['attendance_rate']);
        $this->assertSame($count, $features['eligible_session_count']);
    }

    public static function verifiedAttendanceStatuses(): array
    {
        return [
            'present' => ['present', 100.0, 1], 'late' => ['late', 100.0, 1],
            'absent' => ['absent', 0.0, 1], 'excused' => ['excused', null, 0],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ineligibleAttendanceSessions')]
    public function test_non_finalized_or_unavailable_sessions_are_ignored(string $status, ?string $finalizedAt): void
    {
        $this->attendanceSession([391 => 'present'], $status, $finalizedAt);
        $features = app(StudentRiskFeatureService::class)->build(391, 1, $this->cutoff);
        $this->assertNull($features['attendance_rate']);
        $this->assertSame(0, $features['eligible_session_count']);
    }

    public static function ineligibleAttendanceSessions(): array
    {
        return [
            'active' => ['active', null], 'cancelled' => ['cancelled', null],
            'after cutoff' => ['finalized', '2026-09-30 17:00:01'],
            'missing finalization timestamp' => ['finalized', null],
        ];
    }

    public function test_frozen_roster_survives_withdrawal_and_excludes_later_enrollments(): void
    {
        $this->attendanceSession([391 => 'late', 392 => 'absent']);
        DB::table('class_student')->where('user_id', 391)->delete();
        $service = app(StudentRiskFeatureService::class);
        $features = $service->build(391, 1, $this->cutoff);
        $this->assertSame(100.0, $features['attendance_rate']);
        $this->assertSame(1, $features['eligible_session_count']);
        // Existing and newly added current members cannot acquire historical eligibility.
        foreach ([393, 394] as $userId) {
            if ($userId === 394) {
                $this->insert('users', ['id' => $userId, 'role' => 'student']);
                $this->insert('class_student', ['user_id' => $userId, 'lab_session_id' => 1, 'joined_at' => '2026-10-01 09:00:00']);
            }
            $features = $service->build($userId, 1, Carbon::parse('2026-10-02 09:00:00'));
            $this->assertNull($features['attendance_rate']);
            $this->assertSame(0, $features['eligible_session_count']);
        }
        $this->assertSame(2, DB::table('attendance_session_students')->count());
    }

    public function test_pending_anomaly_is_logged_without_identity_and_export_continues(): void
    {
        Log::spy();
        $this->attendanceSession([391 => 'pending']);
        $this->attendanceSession([391 => 'excused']);
        $stream = fopen('php://temp', 'w+');
        try {
            app(StudentRiskDatasetService::class)->exportClassesCsv([1], $this->cutoff, $stream);
            [, $rows] = $this->readCsv($stream);
            $this->assertSame('', $rows[0]['attendance_rate']);
            $this->assertSame('0', $rows[0]['eligible_session_count']);
        } finally {
            fclose($stream);
        }
        Log::shouldHaveReceived('warning')->once()->with(
            'Finalized attendance contains pending roster entries; excluded from risk features.',
            ['lab_session_id' => 1, 'feature_cutoff_at' => '2026-09-30 17:00:00', 'pending_entry_count' => 1]
        );
    }

    public function test_mixed_sessions_count_meetings_not_dates_and_ignore_other_classes(): void
    {
        foreach (['present', 'late', 'absent', 'excused', 'pending'] as $status) {
            $this->attendanceSession([391 => $status]);
        }
        $this->insert('lab_sessions', ['id' => 2]);
        $this->attendanceSession([391 => 'absent'], classId: 2);
        $this->attendanceSession([500 => 'present']); // Professor-only event.
        $features = app(StudentRiskFeatureService::class)->build(391, 1, $this->cutoff);
        $this->assertSame(66.67, $features['attendance_rate']);
        $this->assertSame(3, $features['eligible_session_count']);
    }

    public function test_verified_snapshots_do_not_reuse_legacy_version_at_same_cutoff(): void
    {
        $service = app(StudentRiskFeatureService::class);
        $old = $service->build(391, 1, $this->cutoff);
        $old['feature_version'] = 'v4';
        $old['attendance_rate'] = 100;
        $old['eligible_session_count'] = 99;
        \App\Models\StudentFeatureSnapshot::create($old);
        $snapshot = $service->snapshot(391, 1, $this->cutoff);
        $this->assertSame('v5', $snapshot->feature_version);
        $this->assertNull($snapshot->attendance_rate);
        $this->assertSame(0, $snapshot->eligible_session_count);
        $this->assertSame(2, DB::table('student_feature_snapshots')->count());
        $this->assertEquals(100, DB::table('student_feature_snapshots')->where('feature_version', 'v4')->value('attendance_rate'));
    }

    private function addOtherClasses(): void
    {
        foreach ([2 => true, 3 => false, 4 => true, 5 => true, 6 => true] as $id => $active) {
            $this->insert('lab_sessions', [
                'id' => $id, 'class_code' => 'CCS-'.$id, 'subject_name' => 'Subject '.$id,
                'program' => 'BSIT', 'year_level' => '3', 'section' => null, 'is_active' => $active,
            ]);
        }
        foreach ([2, 3, 5] as $classId) {
            $this->insert('class_student', [
                'user_id' => 391, 'lab_session_id' => $classId,
                'joined_at' => $classId === 5 ? '2026-10-01 09:00:00' : '2026-09-01 09:00:00',
            ]);
        }
        $this->insert('users', ['id' => 400, 'role' => 'professor']);
        $this->insert('class_student', ['user_id' => 400, 'lab_session_id' => 6, 'joined_at' => '2026-09-01 09:00:00']);
        $this->insert('class_student', ['user_id' => 391, 'lab_session_id' => 2, 'joined_at' => '2026-09-01 09:00:00']);
    }

    private function readCsv($stream): array
    {
        rewind($stream);
        $header = fgetcsv($stream, escape: '');
        $rows = [];
        while (($values = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = array_combine($header, $values);
        }

        return [$header, $rows];
    }

    public function test_ml_ready_schema_is_complete_private_nullable_and_preserves_calculations(): void
    {
        config(['risk_features.grouping_secret' => 'test-only-grouping-secret']);
        $this->addOtherClasses();
        $this->insert('quizzes', ['id' => 1, 'subject_id' => 1, 'learning_topic_id' => 7]);
        $this->insert('quiz_attempts', ['user_id' => 391, 'quiz_id' => 1, 'score' => 8, 'total_points' => 10, 'is_simulated' => false]);
        $service = app(StudentRiskFeatureService::class);
        $before = $service->build(391, 1, $this->cutoff);
        $stream = fopen('php://temp', 'w+');
        app(StudentRiskDatasetService::class)->exportClassesCsv([1, 2], $this->cutoff, $stream, false, true);
        [$header, $rows] = $this->readCsv($stream); fclose($stream);
        $schema = app(\App\Services\RiskFeatureSchema::class);
        $this->assertSame(['quiz_avg', 'quiz_attempt_count', 'task_avg', 'submission_rate', 'late_submission_count',
            'materials_accessed', 'material_session_count', 'reading_total_seconds', 'reading_avg_seconds',
            'activity_count', 'activity_duration_seconds'], $schema->core());
        $this->assertSame($schema->columns(true, true), $header);
        foreach ([...$schema->core(), ...$schema->optional(), ...$schema->structured()] as $column) $this->assertContains($column, $header);
        foreach (['current_academic_grade', 'violation_count', 'user_id', 'student_name', 'name', 'email', 'school_id', 'student_number', 'final_academic_outcome', 'academic_risk_status', 'topic_task_average'] as $column) $this->assertNotContains($column, $header);
        $sameStudent = array_values(array_filter($rows, fn ($row) => $row['student_group_key'] === $schema->groupingKey(391)));
        $this->assertCount(2, $sameStudent);
        $this->assertSame(['1', '2'], array_column($sameStudent, 'lab_session_id'));
        $this->assertNotSame($schema->groupingKey(391), $schema->groupingKey(392));
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $sameStudent[0]['student_group_key']);
        $this->assertSame('', $sameStudent[0]['attendance_rate']);
        $this->assertSame('0', $sameStudent[0]['eligible_session_count']);
        $this->assertSame('0', $sameStudent[0]['assigned_task_count']);
        $this->assertSame('v5', $sameStudent[0]['feature_version']);
        $this->assertEquals($before, $service->build(391, 1, $this->cutoff));
        foreach ($schema->core() as $column) $this->assertEquals($service->diagnosticInput($before)[$column] ?? '', $sameStudent[0][$column]);
        $this->assertEquals(80, json_decode($sameStudent[0]['topic_quiz_performance_json'], true)[7]['average']);
        $this->assertSame('{}', $sameStudent[0]['topic_task_performance_json']);
        $this->assertSame([...$schema->core(), ...$schema->optional(), ...$schema->structured()], array_values(array_intersect([...$schema->core(), ...$schema->optional(), ...$schema->structured()], array_keys($service->modelInput($before)))));
        $this->assertArrayNotHasKey('current_academic_grade', $service->modelInput($before));
        $this->assertArrayNotHasKey('eligible_session_count', $service->modelInput($before));
    }

    public function test_both_export_commands_support_ml_ready_and_diagnostic_export_stays_compatible(): void
    {
        config(['risk_features.grouping_secret' => 'test-only-grouping-secret']);
        foreach (['risk:export-class', 'risk:export-all-classes'] as $command) {
            $filename = 'test-ml-ready-'.bin2hex(random_bytes(5)).'.csv';
            $path = storage_path('app/risk-datasets/'.$filename);
            $arguments = ['--cutoff' => $this->cutoff->format('Y-m-d H:i:s'), '--ml-ready' => true,
                '--output' => $command === 'risk:export-class' ? $path : $filename];
            if ($command === 'risk:export-class') $arguments['lab_session_id'] = 1;
            try {
                $this->artisan($command, $arguments)->assertSuccessful();
                $stream = fopen($path, 'r'); $header = fgetcsv($stream, escape: ''); fclose($stream);
                $this->assertContains('student_group_key', $header);
                $this->assertNotContains('user_id', $header);
            } finally { if (is_file($path)) unlink($path); }
        }
        $stream = fopen('php://temp', 'w+');
        app(StudentRiskDatasetService::class)->exportCsv(1, $this->cutoff, $stream);
        [$header] = $this->readCsv($stream); fclose($stream);
        $this->assertSame(config('risk_features.diagnostic_columns'), $header);
        $this->assertContains('current_academic_grade', $header);
        $this->assertContains('violation_count', $header);
    }

    public function test_multiple_classes_preserve_features_and_include_each_student_class_pair_once(): void
    {
        $this->addOtherClasses();
        foreach ([1 => 7, 2 => 8] as $classId => $topicId) {
            $this->insert('quizzes', ['id' => $classId, 'subject_id' => $classId, 'learning_topic_id' => $topicId]);
            $this->insert('quiz_attempts', ['user_id' => 391, 'quiz_id' => $classId, 'score' => $classId === 1 ? 8 : 6, 'total_points' => 10, 'is_simulated' => false]);
            $this->insert('quiz_attempts', ['user_id' => 391, 'quiz_id' => $classId, 'score' => 10, 'total_points' => 10, 'is_simulated' => true]);
        }
        $datasets = app(StudentRiskDatasetService::class);
        $stream = fopen('php://temp', 'w+');
        try {
            $summary = $datasets->exportClassesCsv([2, 1, 2], $this->cutoff, $stream);
            [$header, $rows] = $this->readCsv($stream);
        } finally {
            fclose($stream);
        }
        $this->assertSame(3, $summary['row_count']);
        $this->assertSame([2, 1], array_column($summary['classes'], 'row_count'));
        $this->assertSame(['391:1', '392:1', '391:2'], array_map(fn ($row) => $row['user_id'].':'.$row['lab_session_id'], $rows));
        $this->assertSame(['2026-09-30 17:00:00'], array_values(array_unique(array_column($rows, 'feature_cutoff_at'))));
        $this->assertSame('CCS-2', $rows[2]['class_code']);
        $this->assertSame('Subject 2', $rows[2]['subject_name']);
        $this->assertSame('BSIT', $rows[2]['program']);
        $this->assertSame('3', $rows[2]['year_level']);
        $this->assertSame('', $rows[2]['section']);
        $this->assertEquals(80, $rows[0]['quiz_avg']);
        $this->assertEquals(60, $rows[2]['quiz_avg']);
        $this->assertSame('1', $rows[2]['quiz_attempt_count']);
        $this->assertEquals(80, json_decode($rows[0]['topic_quiz_performance_json'], true)[7]['average']);
        $this->assertArrayNotHasKey(8, json_decode($rows[0]['topic_quiz_performance_json'], true));
        $this->assertEquals(60, json_decode($rows[2]['topic_quiz_performance_json'], true)[8]['average']);
        $this->assertSame('{}', $rows[1]['topic_quiz_performance_json']);
        $this->assertSame('{}', $rows[1]['topic_task_performance_json']);
        $this->assertNotContains('topic_quiz_average__topic_7', $header);
        foreach (['quiz_avg', 'task_avg', 'attendance_rate', 'submission_rate', 'reading_avg_seconds', 'current_academic_grade', 'topic_task_average'] as $key) {
            $this->assertSame('', $rows[1][$key]);
        }
        $this->assertNotContains('final_academic_outcome', $header);
        $this->assertNotContains('academic_risk_status', $header);
        $single = fopen('php://temp', 'w+');
        try {
            $datasets->exportCsv(1, $this->cutoff, $single);
            [$singleHeader, $singleRows] = $this->readCsv($single);
            $this->assertSame([], array_values(array_diff($singleHeader, $header)));
            foreach ($singleRows as $i => $singleRow) {
                $this->assertSame($singleRow, array_intersect_key($rows[$i], $singleRow));
            }
        } finally {
            fclose($single);
        }
        $this->assertSame(0, DB::table('student_feature_snapshots')->count());
    }

    public function test_command_without_classes_selects_only_active_classes_with_eligible_students(): void
    {
        $this->addOtherClasses();
        $filename = 'test-all-classes-'.bin2hex(random_bytes(6)).'.csv';
        $path = storage_path('app/risk-datasets/'.$filename);
        try {
            $this->artisan('risk:export-all-classes', [
                '--cutoff' => $this->cutoff->format('Y-m-d H:i:s'), '--output' => $filename,
            ])->expectsOutputToContain('Exported 3 rows across 2 classes')->assertSuccessful();
            $stream = fopen($path, 'r');
            try {
                [, $rows] = $this->readCsv($stream);
            } finally {
                fclose($stream);
            }
            $this->assertSame(['1', '2'], array_values(array_unique(array_column($rows, 'lab_session_id'))));
            $this->assertCount(3, $rows);
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function test_explicit_classes_command_allows_inactive_classes_and_saves_pairs_once(): void
    {
        $this->addOtherClasses();
        $filename = 'test-selected-classes-'.bin2hex(random_bytes(6)).'.csv';
        $path = storage_path('app/risk-datasets/'.$filename);
        try {
            $this->artisan('risk:export-all-classes', [
                '--classes' => '3,2,2', '--cutoff' => $this->cutoff->format('Y-m-d H:i:s'),
                '--output' => $filename, '--save' => true,
            ])->expectsOutputToContain('Exported 2 rows across 2 classes')->assertSuccessful();
            $this->assertSame(2, DB::table('student_feature_snapshots')->count());
            $this->assertEquals([2, 3], DB::table('student_feature_snapshots')->orderBy('lab_session_id')->pluck('lab_session_id')->all());
            $this->assertSame(['2026-09-30 17:00:00'], DB::table('student_feature_snapshots')->distinct()->pluck('feature_cutoff_at')->all());
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function test_all_classes_command_requires_valid_cutoff_class_list_and_output_filename(): void
    {
        $this->artisan('risk:export-all-classes')->assertFailed();
        foreach (['', '1,', '1,no', '0', '-1'] as $list) {
            $this->artisan('risk:export-all-classes', ['--classes' => $list, '--cutoff' => '2026-09-30 17:00:00'])->assertFailed();
        }
        $this->artisan('risk:export-all-classes', ['--cutoff' => '2026-02-30 17:00:00'])->assertFailed();
        $this->artisan('risk:export-all-classes', ['--cutoff' => '2026-09-30 17:00:00', '--output' => '../outside.csv'])->assertFailed();
        $stream = fopen('php://temp', 'w+');
        try {
            $this->expectException(\InvalidArgumentException::class);
            app(StudentRiskDatasetService::class)->exportClassesCsv([1, 999], $this->cutoff, $stream);
        } finally {
            fclose($stream);
        }
    }
}
