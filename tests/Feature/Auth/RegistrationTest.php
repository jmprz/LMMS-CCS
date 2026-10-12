<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'school_id' => 'TEST-202600001',
            'role' => 'student',
            'program' => 'BSIT',
            'year_level' => 1,
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        $response->assertRedirect(route('student.dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'school_id' => 'TEST-202600001', 'first_name' => 'TEST', 'last_name' => 'USER',
            'name' => 'TEST USER', 'role' => 'student', 'program' => 'BSIT', 'year_level' => 1,
        ]);
    }
}
