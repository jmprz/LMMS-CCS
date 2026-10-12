<?php

namespace Tests\Feature;

use App\Models\ActivityDurationSession;
use App\Models\AttendanceSession;
use App\Models\LabSession;
use App\Models\LearningTopic;
use App\Models\Material;
use App\Models\MaterialLog;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Task;
use App\Models\User;
use App\Services\RiskCollectionStatusService;
use App\Services\StudentRiskFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RiskCollectionStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $professor;

    private LabSession $class;

    private LabSession $emptyClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Carbon::setTestNow('2026-10-03 16:00:00');
        config(['risk_target.passing_grade' => null]);
        $this->professor = $this->user('professor');
        $this->student = $this->user('student');
        $this->class = LabSession::create(['class_code' => 'COLLECT', 'subject_name' => 'Programming', 'faculty_id' => $this->professor->id, 'is_active' => true]);
        $this->emptyClass = LabSession::create(['class_code' => 'EMPTY', 'subject_name' => 'No activities', 'faculty_id' => $this->professor->id, 'is_active' => true]);
        foreach ([$this->class, $this->emptyClass] as $class) {
            DB::table('class_student')->insert(['user_id' => $this->student->id, 'lab_session_id' => $class->id, 'joined_at' => now()->subDay()]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $key = (string) Str::uuid();

        return User::findOrFail(DB::table('users')->insertGetId(['first_name' => 'Private', 'last_name' => 'Identity', 'name' => 'Private Identity',
            'school_id' => $key, 'email' => $key.'@example.test', 'role' => $role, 'password' => bcrypt('password')]));
    }

    private function academicData(): void
    {
        $topic = LearningTopic::create(['name' => 'Loops', 'slug' => 'loops']);
        $quiz = Quiz::create(['title' => 'Quiz', 'subject_id' => $this->class->id, 'time_limit' => 10, 'learning_topic_id' => $topic->id]);
        Quiz::create(['title' => 'Untagged', 'subject_id' => $this->class->id, 'time_limit' => 10]);
        QuizAttempt::create(['quiz_id' => $quiz->id, 'user_id' => $this->student->id, 'score' => 8, 'total_points' => 10, 'total_questions' => 1, 'is_simulated' => false]);
        QuizAttempt::create(['quiz_id' => $quiz->id, 'user_id' => $this->student->id, 'score' => 10, 'total_points' => 10, 'total_questions' => 1, 'is_simulated' => true]);
        Task::create(['title' => 'Due', 'description' => 'Test', 'subject_id' => $this->class->id, 'deadline' => now()->subHour(), 'points' => 10, 'learning_topic_id' => $topic->id]);
        Task::create(['title' => 'Future', 'description' => 'Test', 'subject_id' => $this->class->id, 'deadline' => now()->addDay(), 'points' => 10]);
    }

    public function test_academic_observations_empty_class_tagging_and_shared_cutoff(): void
    {
        $this->academicData();
        $before = app(StudentRiskFeatureService::class)->build($this->student->id, $this->class->id, now());
        $report = app(RiskCollectionStatusService::class)->report();
        $this->assertSame('v5', $report['feature_version']);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $report['cutoff']);
        $this->assertSame(1, $report['overall']['metrics']['enrolled_students']);
        $this->assertSame(2, $report['overall']['metrics']['student_class_rows']);
        $full = $report['classes'][0];
        $empty = $report['classes'][1];
        $this->assertSame(2, $full['metrics']['quizzes_total']);
        $this->assertSame(1, $full['metrics']['tagged_quizzes']);
        $this->assertSame(1, $full['metrics']['untagged_quizzes']);
        $this->assertSame(1, $full['metrics']['real_quiz_attempts']);
        $this->assertSame(1, $full['metrics']['tagged_tasks']);
        $this->assertSame(1, $full['metrics']['untagged_tasks']);
        $this->assertSame(1, $full['metrics']['eligible_due_tasks']);
        $this->assertSame(100.0, $full['coverage']['quiz_avg']['coverage_percent']);
        $this->assertSame(50.0, $report['overall']['coverage']['quiz_avg']['coverage_percent']);
        $this->assertSame(100.0, $full['coverage']['submission_rate']['coverage_percent']);
        $this->assertSame(100.0, $full['coverage']['topic_quiz_performance']['coverage_percent']);
        $this->assertSame(0, $empty['metrics']['quizzes_total']);
        $this->assertContains('No data yet', array_column($empty['flags'], 'status'));
        $this->assertEquals($before, app(StudentRiskFeatureService::class)->build($this->student->id, $this->class->id, now()));
        $this->assertDatabaseCount('student_feature_snapshots', 0);
    }

    public function test_legacy_vs_verified_material_activity_and_attendance_coverage(): void
    {
        $material = Material::create(['title' => 'PDF', 'type' => 'pdf', 'content' => 'test.pdf', 'lab_session_id' => $this->class->id]);
        MaterialLog::create(['user_id' => $this->student->id, 'material_id' => $material->id, 'opened_at' => now()->subHour(), 'duration_seconds' => 999]);
        MaterialLog::create(['user_id' => $this->student->id, 'material_id' => $material->id, 'opened_at' => now()->subHour(),
            'collection_version' => 'visible_v1', 'measurement_mode' => 'internal_visible', 'duration_seconds' => 20]);
        MaterialLog::create(['user_id' => $this->student->id, 'material_id' => $material->id, 'opened_at' => now()->subHour(),
            'collection_version' => 'visible_v1', 'measurement_mode' => 'external_open', 'duration_seconds' => null]);
        ActivityDurationSession::create(['user_id' => $this->student->id, 'lab_session_id' => $this->class->id, 'client_session_key' => Str::uuid(),
            'collection_version' => 'generic_ui_v1', 'opened_at' => now()->subHour(), 'last_heartbeat_at' => now(), 'duration_seconds' => 10]);
        DB::table('activity_logs')->insert(['user_id' => $this->student->id, 'lab_session_id' => $this->emptyClass->id, 'log_type' => 'submission', 'content' => 'Legacy', 'duration_seconds' => 999999, 'created_at' => now(), 'updated_at' => now()]);
        $session = AttendanceSession::create(['lab_session_id' => $this->class->id, 'session_date' => now()->toDateString(),
            'started_at' => now()->subHour(), 'finalized_at' => now(), 'status' => 'finalized', 'created_by' => $this->professor->id]);
        DB::table('attendance_session_students')->insert(['attendance_session_id' => $session->id, 'user_id' => $this->student->id, 'status' => 'present']);
        foreach (['active', 'cancelled'] as $status) {
            AttendanceSession::create(['lab_session_id' => $this->class->id, 'session_date' => now()->toDateString(), 'started_at' => now(), 'status' => $status, 'created_by' => $this->professor->id]);
        }
        $report = app(RiskCollectionStatusService::class)->report();
        $class = $report['classes'][0];
        $this->assertSame(1, $class['metrics']['legacy_material_duration_records']);
        $this->assertSame(2, $class['metrics']['new_visible_v1_material_sessions']);
        $this->assertSame(1, $class['metrics']['verified_material_duration_measurements']);
        $this->assertSame(100.0, $class['coverage']['verified_material_duration']['coverage_percent']);
        $this->assertSame(50.0, $report['overall']['coverage']['activity_duration_seconds']['coverage_percent']);
        $this->assertSame(1, $class['metrics']['students_with_verified_activity_duration']);
        $this->assertSame(3, $class['metrics']['attendance_sessions_total']);
        $this->assertSame(1, $class['metrics']['attendance_sessions_finalized']);
        $this->assertSame(1, $class['metrics']['attendance_sessions_active']);
        $this->assertSame(1, $class['metrics']['attendance_sessions_cancelled']);
        $this->assertSame(100.0, $class['coverage']['attendance_rate']['coverage_percent']);
        $this->assertSame(0.0, $report['classes'][1]['coverage']['activity_duration_seconds']['coverage_percent']);
    }

    public function test_no_outcomes_passing_criterion_privacy_json_csv_and_class_selection(): void
    {
        $report = app(RiskCollectionStatusService::class)->report([$this->class->id]);
        $this->assertSame('NOT CONFIGURED', $report['passing_criterion_status']);
        $this->assertSame('DISABLED', $report['target_generation']);
        $this->assertSame(0, $report['overall']['metrics']['official_outcomes']);
        $this->assertSame(1, $report['overall']['metrics']['students_awaiting_outcomes']);
        $this->assertSame('NOT READY / awaiting official outcomes', $report['supervised_training_status']);
        $this->artisan('risk:collection-status', ['--classes' => (string) $this->class->id])->expectsOutputToContain('NOT CONFIGURED')
            ->doesntExpectOutputToContain('Private Identity')->doesntExpectOutputToContain($this->student->school_id)->assertSuccessful();
        $this->artisan('risk:collection-status', ['--classes' => 'invalid'])->assertFailed();
        $this->artisan('risk:collection-status', ['--classes' => '999999'])->assertFailed();
        config(['risk_target.passing_grade' => '3.00']);
        $report = app(RiskCollectionStatusService::class)->report();
        $this->assertSame('CONFIGURED', $report['passing_criterion_status']);
        $this->assertSame('NOT READY / awaiting official outcomes', $report['supervised_training_status']);
        foreach ([$this->student->school_id, $this->student->email, 'Private Identity', 'academic_risk_status'] as $sensitive) {
            $this->assertStringNotContainsString($sensitive, json_encode($report));
        }
        $filename = 'test-collection-'.Str::uuid().'.csv';
        $path = storage_path('app/risk-datasets/'.$filename);
        try {
            $this->artisan('risk:collection-status', ['--json' => true, '--output' => $filename])->assertSuccessful();
            $text = file_get_contents($path);
            $this->assertStringNotContainsString($this->student->school_id, $text);
            $this->assertStringNotContainsString($this->student->email, $text);
            $this->assertStringNotContainsString('user_id', $text);
            $this->artisan('risk:collection-status', ['--output' => $filename])->assertFailed();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->assertDatabaseCount('subject_grades', 0);
    }
}
