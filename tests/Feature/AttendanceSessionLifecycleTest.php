<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\LabSession;
use App\Models\User;
use App\Services\AttendanceSessionService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceSessionLifecycleTest extends TestCase
{
    private LabSession $class;

    private User $professor;

    private AttendanceSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role');
            $table->boolean('is_activated')->default(true);
            $table->timestamps();
        });
        Schema::create('lab_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('users');
            $table->string('class_code');
            $table->boolean('is_active')->default(true);
            $table->integer('violation_warning_threshold')->default(3);
            $table->timestamps();
        });
        Schema::create('class_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('lab_session_id')->constrained('lab_sessions');
            $table->timestamp('joined_at');
            $table->boolean('is_present')->default(false);
            $table->integer('violation_count')->default(0);
            $table->boolean('is_screen_blocked')->default(false);
            $table->timestamp('screen_blocked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('lab_session_id');
            $table->date('attendance_date');
            $table->time('joined_at');
            $table->time('left_at')->nullable();
            $table->string('status')->default('present');
            $table->timestamps();
        });
        foreach (['2026_10_02_000001_create_attendance_sessions_table.php', '2026_10_02_000002_create_attendance_session_students_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->travelTo(Carbon::parse('2026-10-02 09:00:00'));
        foreach ([1 => 'professor', 2 => 'student', 3 => 'student', 4 => 'student', 5 => 'student', 6 => 'professor'] as $id => $role) {
            DB::table('users')->insert(['id' => $id, 'name' => 'Test '.$id, 'role' => $role, 'is_activated' => true]);
        }
        $this->professor = User::findOrFail(1);
        $this->class = LabSession::create(['faculty_id' => 1, 'class_code' => 'TEST', 'is_active' => true]);
        foreach ([1, 2, 3, 4, 5] as $userId) {
            $this->enroll($userId);
        }
        $this->service = app(AttendanceSessionService::class);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function enroll(int $userId): void
    {
        DB::table('class_student')->insert(['user_id' => $userId, 'lab_session_id' => $this->class->id, 'joined_at' => now()->subDay()]);
    }

    public function test_professor_start_snapshots_students_only_and_repeated_start_is_idempotent(): void
    {
        $this->enroll(2); // Duplicate membership must not duplicate the roster.
        $this->actingAs($this->professor)->postJson(route('professor.attendance.start', $this->class), ['late_after_minutes' => 10])
            ->assertOk()->assertJsonPath('counts.expected', 4)->assertJsonPath('counts.pending', 4)->assertJsonPath('session.status', 'active');
        $session = AttendanceSession::firstOrFail();
        $this->assertEquals([2, 3, 4, 5], $session->students()->orderBy('user_id')->pluck('user_id')->all());
        $this->assertSame(1, $session->created_by);
        $this->assertSame($session->id, $this->service->start($this->class, $this->professor, 30)->id);
        $this->assertSame(1, AttendanceSession::count());
        $this->assertSame(0, DB::table('attendances')->count());
    }

    public function test_checkin_is_idempotent_lateness_has_a_boundary_and_finalization_preserves_statuses(): void
    {
        $session = $this->service->start($this->class, $this->professor, 10);
        $this->actingAs(User::find(2))->postJson(route('student.mark-present', $this->class), ['attendance_session_id' => $session->id])
            ->assertOk()->assertJsonPath('verified_attendance.status', 'present');
        $firstTime = $session->students()->where('user_id', 2)->first()->checked_in_at->toDateTimeString();
        $this->travelTo(now()->addMinutes(10));
        $this->service->checkIn($this->class, User::find(3), $session->id);
        $this->assertSame('present', $session->students()->where('user_id', 3)->value('status'));
        $this->travelTo(now()->addSecond());
        $this->service->checkIn($this->class, User::find(2), $session->id);
        $this->service->checkIn($this->class, User::find(4), $session->id);
        $this->service->excuse($session, $this->professor, 5, true);
        $this->service->checkIn($this->class, User::find(5), $session->id);
        $this->assertSame(4, $session->students()->count());
        $this->assertSame($firstTime, $session->students()->where('user_id', 2)->first()->checked_in_at->toDateTimeString());
        $this->actingAs($this->professor)->postJson(route('professor.attendance.finalize', $session))->assertOk()
            ->assertJsonPath('counts.present', 2)->assertJsonPath('counts.late', 1)->assertJsonPath('counts.excused', 1);
        $this->assertSame('finalized', $session->fresh()->status);
        $this->assertNotNull($session->fresh()->finalized_at);
        $this->assertSame(0, DB::table('attendances')->count());
        $this->actingAs(User::find(2))->postJson(route('student.mark-present', $this->class), ['attendance_session_id' => $session->id])->assertStatus(409);
    }

    public function test_finalization_converts_pending_to_absent_and_closed_records_are_immutable(): void
    {
        $session = $this->service->start($this->class, $this->professor);
        $this->service->checkIn($this->class, User::find(2));
        $this->service->close($session, $this->professor);
        $this->assertSame(3, $session->students()->where('status', 'absent')->count());
        $this->assertSame(1, $session->students()->where('status', 'present')->count());
        $finalized = $session->fresh()->finalized_at->toDateTimeString();
        $this->travelTo(now()->addDay());
        $this->service->close($session, $this->professor);
        $this->assertSame($finalized, $session->fresh()->finalized_at->toDateTimeString());
        $this->actingAs($this->professor)->postJson(route('professor.attendance.cancel', $session))->assertStatus(409);
        $this->patchJson(route('professor.attendance.excuse', [$session, 3]), ['excused' => true])->assertStatus(409);
    }

    public function test_cancellation_keeps_pending_and_does_not_qualify_as_finalized(): void
    {
        $session = $this->service->start($this->class, $this->professor);
        $this->actingAs($this->professor)->postJson(route('professor.attendance.cancel', $session))->assertOk()->assertJsonPath('session.status', 'cancelled');
        $this->assertNull($session->fresh()->finalized_at);
        $this->assertSame(4, $session->students()->where('status', 'pending')->count());
        $this->assertSame(0, AttendanceSession::where('status', 'finalized')->whereNotNull('finalized_at')->count());
        $this->postJson(route('professor.attendance.finalize', $session))->assertStatus(409);
        $next = $this->service->start($this->class, $this->professor);
        $this->assertNotSame($session->id, $next->id);
    }

    public function test_roster_survives_membership_changes_and_late_enrollment_is_not_added(): void
    {
        DB::table('class_student')->where('user_id', 5)->delete();
        $session = $this->service->start($this->class, $this->professor);
        $this->enroll(5);
        DB::table('class_student')->where('user_id', 2)->delete();
        $this->assertSame(3, $session->students()->count());
        $this->assertSame(1, $session->students()->where('user_id', 2)->count());
        $this->assertSame(0, $session->students()->where('user_id', 5)->count());
        $this->actingAs(User::find(5))->postJson(route('student.mark-present', $this->class))->assertStatus(409);
        $this->assertSame(0, DB::table('attendances')->count());
        $this->service->close($session, $this->professor);
        $this->assertSame('absent', $session->students()->where('user_id', 2)->value('status'));
    }

    public function test_heartbeat_and_departure_do_not_create_attendance_and_legacy_is_preserved(): void
    {
        DB::table('attendances')->insert(['user_id' => 2, 'lab_session_id' => $this->class->id, 'attendance_date' => '2026-09-01',
            'joined_at' => '09:00:00', 'status' => 'present', 'created_at' => '2026-09-01 09:00:00', 'updated_at' => '2026-09-01 09:00:00']);
        $legacy = DB::table('attendances')->get()->toJson();
        $session = $this->service->start($this->class, $this->professor);
        $this->actingAs(User::find(2))->postJson(route('student.heartbeat', $this->class))->assertOk()->assertJsonPath('attendance_roster_status', 'pending');
        $this->assertNull($session->students()->where('user_id', 2)->first()->checked_in_at);
        $this->postJson(route('student.mark-present', $this->class))->assertOk();
        $this->postJson(route('student.stop-presenting', $this->class))->assertOk();
        $this->assertNotNull($session->students()->where('user_id', 2)->first()->left_at);
        $this->postJson(route('student.heartbeat', $this->class))->assertOk();
        $this->assertNull($session->students()->where('user_id', 2)->first()->left_at);
        $this->assertSame($legacy, DB::table('attendances')->get()->toJson());
        $this->service->close($session, $this->professor, true);
        $this->postJson(route('student.mark-present', $this->class))->assertOk();
        $this->postJson(route('student.mark-present', $this->class))->assertOk();
        $this->assertSame(1, DB::table('attendances')->where('attendance_date', '2026-10-02')->count());
        $this->assertSame('09:00:00', DB::table('attendances')->where('attendance_date', '2026-09-01')->value('joined_at'));
    }

    public function test_permissions_roster_privacy_empty_class_and_invalid_grace(): void
    {
        $this->actingAs(User::find(6))->postJson(route('professor.attendance.start', $this->class))->assertForbidden();
        $this->getJson(route('professor.attendance.show', $this->class))->assertForbidden();
        $this->actingAs(User::find(2))->getJson(route('professor.attendance.show', $this->class))->assertForbidden();
        $this->actingAs($this->professor)->postJson(route('professor.attendance.start', $this->class), ['late_after_minutes' => 121])->assertUnprocessable();
        DB::table('class_student')->delete();
        $this->postJson(route('professor.attendance.start', $this->class))->assertUnprocessable();
        $this->assertSame(0, AttendanceSession::count());
    }

    public function test_database_enforces_unique_roster_key(): void
    {
        $session = $this->service->start($this->class, $this->professor);
        $this->expectException(QueryException::class);
        $session->students()->create(['user_id' => 2, 'status' => 'pending']);
    }

    public function test_logout_records_departure_without_changing_attendance_status(): void
    {
        $session = $this->service->start($this->class, $this->professor);
        $this->service->checkIn($this->class, User::find(2));
        $this->actingAs(User::find(2))->post('/logout')->assertRedirect('/');
        $row = $session->students()->where('user_id', 2)->first();
        $this->assertNotNull($row->left_at);
        $this->assertSame('present', $row->status);
    }

    public function test_stale_session_cannot_mark_new_session_present_or_write_legacy(): void
    {
        $old = $this->service->start($this->class, $this->professor);
        $this->service->close($old, $this->professor, true);
        $new = $this->service->start($this->class, $this->professor);
        $this->actingAs(User::find(2))->postJson(route('student.mark-present', $this->class), ['attendance_session_id' => $old->id])->assertStatus(409);
        $this->assertSame('pending', $new->students()->where('user_id', 2)->value('status'));
        $this->assertSame(0, DB::table('attendances')->count());
    }

    public function test_professor_controls_render_with_explicit_finalization_and_excusal(): void
    {
        $html = view('professor.classroom.partials.attendance', ['class' => $this->class])->render();
        $this->assertStringContainsString('Start Attendance Session', $html);
        $this->assertStringContainsString('Finalize Attendance', $html);
        $this->assertStringContainsString('Cancel Attendance', $html);
        $this->assertStringContainsString('Excuse', $html);
    }
}
