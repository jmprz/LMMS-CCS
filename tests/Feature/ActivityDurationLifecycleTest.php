<?php

namespace Tests\Feature;

use App\Models\ActivityDurationSession;
use App\Models\LabSession;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityDurationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private LabSession $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Carbon::setTestNow('2026-10-03 12:00:00');
        Http::preventStrayRequests();
        $this->withoutMiddleware([\App\Http\Middleware\EnsureAccountIsActivated::class, \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class]);
        $professor = $this->user('professor');
        $this->student = $this->user('student');
        $this->class = LabSession::create(['class_code' => 'ACTIVITY', 'subject_name' => 'Test', 'faculty_id' => $professor->id]);
        DB::table('class_student')->insert(['user_id' => $this->student->id, 'lab_session_id' => $this->class->id, 'joined_at' => now()->subDay()]);
        $this->actingAs($this->student);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $key = (string) Str::uuid();

        return User::findOrFail(DB::table('users')->insertGetId(['first_name' => 'Test', 'last_name' => $role, 'name' => 'Test',
            'school_id' => $key, 'email' => $key.'@example.test', 'role' => $role, 'password' => bcrypt('password')]));
    }

    private function start(?string $key = null): int
    {
        return $this->postJson(route('student.activity.start', $this->class->id), ['client_session_key' => $key ?? (string) Str::uuid()])
            ->assertOk()->json('session_id');
    }

    private function record(int $id, int $sequence, int $seconds, bool $close = false)
    {
        return $this->postJson(route('student.activity.'.($close ? 'end' : 'heartbeat'), $this->class->id),
            ['session_id' => $id, 'sequence' => $sequence, 'active_seconds' => $seconds]);
    }

    public function test_start_and_heartbeat_retries_are_idempotent_and_bounded(): void
    {
        $key = (string) Str::uuid();
        $id = $this->start($key);
        $this->assertSame($id, $this->start($key));
        $this->assertNull(ActivityDurationSession::find($id)->duration_seconds);
        Carbon::setTestNow(now()->addSeconds(10));
        $this->record($id, 1, 8)->assertOk()->assertJsonPath('duration_seconds', 8);
        $this->record($id, 1, 999)->assertOk()->assertJsonPath('duration_seconds', 8);
        Carbon::setTestNow(now()->addHours(2));
        $this->record($id, 2, 1000)->assertOk()->assertJsonPath('duration_seconds', 28);
        $this->record($id, 3, 900)->assertStatus(422);
        $this->assertDatabaseCount('activity_duration_sessions', 1);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_inactive_interval_is_not_added_and_close_preserves_measured_zero(): void
    {
        $id = $this->start();
        Carbon::setTestNow(now()->addHours(3));
        $this->record($id, 1, 0, true)->assertOk()->assertJsonPath('duration_seconds', 0)->assertJsonPath('closed', true);
        $this->record($id, 2, 800)->assertOk()->assertJsonPath('duration_seconds', 0);
    }

    public function test_new_tab_closes_previous_collector_without_inventing_duration(): void
    {
        $first = $this->start();
        Carbon::setTestNow(now()->addSeconds(10));
        $this->record($first, 1, 8)->assertOk();
        $second = $this->start();
        $this->assertNotSame($first, $second);
        $this->record($first, 2, 16)->assertOk()->assertJsonPath('closed', true)->assertJsonPath('duration_seconds', 8);
        $this->assertNull(ActivityDurationSession::find($second)->duration_seconds);
    }

    public function test_other_student_and_non_enrolled_class_cannot_write_sessions(): void
    {
        $id = $this->start();
        $this->actingAs($this->user('student'));
        $this->record($id, 1, 10)->assertNotFound();
        $this->postJson(route('student.activity.start', $this->class->id), ['client_session_key' => (string) Str::uuid()])->assertForbidden();
    }

    public function test_navigation_does_not_rewrite_historical_duration(): void
    {
        $id = DB::table('activity_logs')->insertGetId(['user_id' => $this->student->id, 'lab_session_id' => $this->class->id,
            'log_type' => 'submission', 'content' => 'Historical', 'duration_seconds' => 999999, 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);
        $before = DB::table('activity_logs')->where('id', $id)->first();
        $this->postJson('/student/log-behavior', ['lab_session_id' => $this->class->id, 'type' => 'navigation', 'detail' => 'Test'])->assertOk();
        $this->assertEquals($before, DB::table('activity_logs')->where('id', $id)->first());
        $this->assertNull(DB::table('activity_logs')->where('log_type', 'navigation')->value('duration_seconds'));
    }

    public function test_submission_records_event_with_null_duration_instead_of_task_age(): void
    {
        $task = Task::create(['subject_id' => $this->class->id, 'title' => 'Old task', 'description' => 'Test', 'points' => 10, 'deadline' => now()->addDay()]);
        $task->created_at = now()->subDays(10);
        $task->save();
        $originalPublic = public_path();
        $temporaryPublic = storage_path('framework/testing/activity-'.Str::uuid());
        $this->app->usePublicPath($temporaryPublic);
        try {
            $this->post(route('student.tasks.submit', $task->id), ['submission' => UploadedFile::fake()->create('test.txt', 1, 'text/plain')],
                ['Accept' => 'application/json'])->assertOk();
            $this->assertNull(DB::table('submissions')->value('duration_seconds'));
            $this->assertDatabaseCount('activity_logs', 1);
            $this->assertSame('submission', DB::table('activity_logs')->value('log_type'));
            $this->assertNull(DB::table('activity_logs')->value('duration_seconds'));
        } finally {
            $this->app->usePublicPath($originalPublic);
            File::deleteDirectory($temporaryPublic);
        }
    }
}
