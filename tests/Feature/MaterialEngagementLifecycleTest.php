<?php

namespace Tests\Feature;

use App\Models\{LabSession, Material, MaterialLog, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\{Carbon, Str};
use Illuminate\Support\Facades\{DB, Mail};
use Tests\TestCase;

class MaterialEngagementLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Material $material;
    private LabSession $class;
    private User $student;
    private User $professor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Carbon::setTestNow('2026-10-02 12:00:00');
        Mail::fake();
        $this->withoutMiddleware([\App\Http\Middleware\EnsureAccountIsActivated::class,
            \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class]);
        $this->student = $this->user('student');
        $this->professor = $this->user('professor');
        $this->class = LabSession::create(['class_code' => 'MATERIAL', 'subject_name' => 'Programming', 'faculty_id' => $this->professor->id]);
        DB::table('class_student')->insert(['user_id' => $this->student->id, 'lab_session_id' => $this->class->id, 'joined_at' => now()->subDay()]);
        $this->material = Material::create(['lab_session_id' => $this->class->id, 'title' => 'PDF', 'type' => 'pdf', 'content' => 'materials/test.pdf']);
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
        $id = DB::table('users')->insertGetId(['first_name' => 'Test', 'last_name' => $role, 'name' => 'Test',
            'school_id' => $key, 'email' => $key.'@example.test', 'role' => $role, 'password' => bcrypt('password')]);

        return User::findOrFail($id);
    }

    private function start(?string $key = null, bool $external = false): int
    {
        return $this->postJson(route('student.materials.log-start', $this->material), ['client_session_key' => $key ?? (string) Str::uuid(), 'external' => $external])
            ->assertOk()->json('session_id');
    }

    private function record(int $id, int $seq, int $seconds, bool $close = false)
    {
        return $this->postJson(route('student.materials.'.($close ? 'log-end' : 'log-heartbeat'), $this->material),
            ['session_id' => $id, 'sequence' => $seq, 'active_seconds' => $seconds]);
    }

    public function test_open_retries_create_one_session_and_no_duration_is_invented(): void
    {
        $key = (string) Str::uuid();
        $id = $this->start($key);
        $this->assertSame($id, $this->start($key));
        $this->assertDatabaseCount('material_logs', 1);
        $log = MaterialLog::findOrFail($id);
        $this->assertNull($log->duration_seconds);
        $this->assertNull($log->closed_at);
        $this->assertSame('internal_visible', $log->measurement_mode);
        Carbon::setTestNow(now()->addDays(2));
        $this->assertNull($log->fresh()->duration_seconds);
        $this->assertNull($log->fresh()->closed_at);
    }

    public function test_heartbeat_bounds_time_and_duplicates_or_out_of_order_requests_do_not_accumulate(): void
    {
        $id = $this->start();
        Carbon::setTestNow(now()->addSeconds(10));
        $this->record($id, 1, 8)->assertOk()->assertJsonPath('duration_seconds', 8);
        $this->record($id, 1, 8)->assertOk()->assertJsonPath('duration_seconds', 8);
        $this->record($id, 1, 999)->assertOk()->assertJsonPath('duration_seconds', 8);
        Carbon::setTestNow(now()->addSeconds(10));
        $this->record($id, 2, 1000)->assertOk()->assertJsonPath('duration_seconds', 18);
        Carbon::setTestNow(now()->addHours(4));
        $this->record($id, 3, 2000)->assertOk()->assertJsonPath('duration_seconds', 38);
        $this->assertDatabaseCount('material_logs', 1);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_hidden_inactive_interval_with_unchanged_counter_does_not_add_duration(): void
    {
        $id = $this->start();
        Carbon::setTestNow(now()->addSeconds(10));
        $this->record($id, 1, 10)->assertOk();
        Carbon::setTestNow(now()->addHours(2));
        $this->record($id, 2, 10)->assertOk()->assertJsonPath('duration_seconds', 10);
        Carbon::setTestNow(now()->addSeconds(10));
        $this->record($id, 3, 15)->assertOk()->assertJsonPath('duration_seconds', 15);
    }

    public function test_close_and_repeated_pagehide_payload_finalize_only_once(): void
    {
        $id = $this->start();
        Carbon::setTestNow(now()->addSeconds(10));
        $this->record($id, 1, 6, true)->assertOk()->assertJsonPath('closed', true);
        $closedAt = MaterialLog::findOrFail($id)->closed_at->format('Y-m-d H:i:s');
        Carbon::setTestNow(now()->addMinute());
        $this->record($id, 1, 6, true)->assertOk();
        $this->record($id, 2, 100)->assertOk()->assertJsonPath('duration_seconds', 6);
        $this->assertSame($closedAt, MaterialLog::findOrFail($id)->closed_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertEquals(6, DB::table('activity_logs')->value('duration_seconds'));
    }

    public function test_external_open_preserves_access_without_claiming_outside_duration(): void
    {
        foreach (['pptx', 'pdf'] as $type) {
            $this->material->update(['type' => $type]);
            $id = $this->start(external: $type === 'pdf');
            Carbon::setTestNow(now()->addMinute());
            $this->record($id, 1, 60, true)->assertOk()->assertJsonPath('duration_seconds', null);
            $this->assertSame('external_open', MaterialLog::findOrFail($id)->measurement_mode);
            $this->assertNotNull(MaterialLog::findOrFail($id)->closed_at);
        }
        $this->assertDatabaseCount('material_logs', 2);
    }

    public function test_legacy_unclosed_rows_are_not_finalized_or_rewritten(): void
    {
        foreach ([null, 12] as $duration) {
            MaterialLog::create(['user_id' => $this->student->id, 'material_id' => $this->material->id,
                'opened_at' => now()->subDay(), 'duration_seconds' => $duration]);
        }
        $before = DB::table('material_logs')->orderBy('id')->get()->toJson();
        $id = $this->start();
        Carbon::setTestNow(now()->addSeconds(5));
        $this->record($id, 1, 5, true)->assertOk();
        $this->assertSame($before, DB::table('material_logs')->where('id', '<', $id)->orderBy('id')->get()->toJson());
        $this->postJson(route('student.materials.log-end', $this->material), ['duration' => 999999])->assertUnprocessable();
        $this->record(1, 2, 999, true)->assertNotFound();
    }

    public function test_authorization_and_validation_prevent_cross_student_and_cross_material_updates(): void
    {
        $id = $this->start();
        $other = $this->user('student');
        $this->actingAs($other);
        $this->postJson(route('student.materials.log-start', $this->material), ['client_session_key' => (string) Str::uuid()])->assertForbidden();
        $this->record($id, 1, 10)->assertNotFound();
        $this->actingAs($this->student);
        $this->record($id, 1, -1)->assertUnprocessable();
        $this->postJson(route('student.materials.log-start', $this->material), ['client_session_key' => 'bad-key'])->assertUnprocessable();
        $otherMaterial = Material::create(['lab_session_id' => $this->class->id, 'title' => 'Other', 'type' => 'pdf', 'content' => 'materials/other.pdf']);
        $this->postJson(route('student.materials.log-heartbeat', $otherMaterial), ['session_id' => $id, 'sequence' => 1, 'active_seconds' => 10])->assertNotFound();
    }

    public function test_professor_material_create_update_and_viewers_still_work(): void
    {
        $this->actingAs($this->professor);
        $this->postJson('/professor/classroom/'.$this->class->id.'/materials', ['title' => 'Video', 'type' => 'youtube', 'content_url' => 'https://www.youtube.com/watch?v=test'])->assertOk();
        $this->putJson('/professor/materials/'.$this->material->id, ['title' => 'Updated PDF'])->assertOk();
        MaterialLog::create(['user_id' => $this->student->id, 'material_id' => $this->material->id, 'opened_at' => now()]);
        $this->getJson('/professor/materials/'.$this->material->id.'/viewers')->assertOk()
            ->assertJsonPath('0.seconds_spent', null)->assertJsonPath('0.is_closed', false);
    }
}
