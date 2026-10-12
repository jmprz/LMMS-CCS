<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_factory_creates_valid_students_with_unique_identifiers(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $users = User::factory()->count(3)->create();
        $this->assertSame(3, $users->pluck('school_id')->unique()->count());
        $this->assertSame(3, $users->pluck('email')->unique()->count());
        foreach ($users as $user) {
            $this->assertNotEmpty($user->first_name);
            $this->assertNotEmpty($user->last_name);
            $this->assertSame($user->first_name.' '.$user->last_name, $user->name);
            $this->assertStringStartsWith('TEST-', $user->school_id);
            $this->assertTrue($user->isStudent());
            $this->assertTrue((bool) $user->is_activated);
            $this->assertSame('enrolled', $user->status);
            $this->assertSame('BSIT', $user->program);
            $this->assertContains($user->year_level, [1, 2, 3, 4]);
            $this->assertNotEmpty($user->section);
            $this->assertNull($user->middle_name);
            $this->assertTrue(Hash::check('password', $user->password));
            $this->assertNotNull($user->email_verified_at);
        }
    }

    public function test_unverified_and_explicit_role_identity_and_nullable_academic_states_are_preserved(): void
    {
        $professor = User::factory()->unverified()->create([
            'first_name' => 'Test', 'last_name' => 'Professor', 'role' => 'professor',
            'program' => null, 'year_level' => null, 'section' => null,
        ]);
        $this->assertTrue($professor->isProfessor());
        $this->assertNull($professor->email_verified_at);
        $this->assertNull($professor->program);
        $this->assertNull($professor->year_level);
        $this->assertNull($professor->section);
        $this->assertSame('Test Professor', $professor->name);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Explicit Test Name', 'status' => 'graduated']);
        $this->assertTrue($admin->isAdmin());
        $this->assertSame('Explicit Test Name', $admin->name);
        $this->assertSame('graduated', $admin->status);
    }

    public function test_unactivated_state_preserves_the_production_profile_access_gate(): void
    {
        $user = User::factory()->unactivated()->create();
        $this->assertFalse((bool) $user->is_activated);
        $this->actingAs($user)->get('/profile')->assertRedirect(route('activation.index'));
    }
}
