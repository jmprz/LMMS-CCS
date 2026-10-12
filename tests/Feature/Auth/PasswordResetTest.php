<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function requestCode(User $user): string
    {
        // Render the real OTP email using an in-memory transport. No email leaves the test.
        config(['mail.default' => 'array']);
        Event::fake([MessageSent::class]);
        $response = $this->post(route('password.email'), ['email' => $user->email]);
        $response->assertSessionHasNoErrors()->assertRedirect(route('password.otp_view'));
        $response->assertSessionHas('password_reset.email', $user->email);
        $response->assertSessionHas('password_reset.verified', false);
        $code = (string) session('password_reset.code');
        $this->assertMatchesRegularExpression('/\A[0-9]{6}\z/', $code);
        $this->assertTrue(session('password_reset.expires_at')->isFuture());
        Event::assertDispatched(MessageSent::class, fn ($event) => $event->message->getTo()[0]->getAddress() === $user->email
            && $event->message->getSubject() === 'LMMS - Password Recovery Security Token'
            && str_contains($event->message->getHtmlBody(), $code));

        return $code;
    }

    public function test_reset_password_request_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_reset_password_code_can_be_requested(): void
    {
        $this->requestCode(User::factory()->create());
        $this->assertGuest();
    }

    public function test_reset_password_screen_can_be_rendered_after_valid_otp(): void
    {
        $code = $this->requestCode(User::factory()->create());
        $this->get(route('password.otp_view'))->assertOk();
        $this->post(route('password.verify_otp'), ['code' => $code])
            ->assertSessionHasNoErrors()->assertRedirect(route('password.reset_view'))
            ->assertSessionHas('password_reset.verified', true);
        $this->get(route('password.reset_view'))->assertOk();
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $this->post(route('password.verify_otp'), ['code' => $code])->assertRedirect(route('password.reset_view'));
        $this->post(route('password.update'), [
            'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'))
            ->assertSessionMissing('password_reset');
        $this->assertTrue(Hash::check('NewPassword123', $user->refresh()->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_otp_does_not_verify_session_or_change_password(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $this->from(route('password.otp_view'))->post(route('password.verify_otp'), [
            'code' => $code === '123456' ? '654321' : '123456',
        ])->assertSessionHasErrors('code')->assertRedirect(route('password.otp_view'))
            ->assertSessionHas('password_reset.verified', false);
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        $this->assertGuest();
    }

    public function test_expired_otp_is_rejected_and_cleared(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $this->withSession(['password_reset' => [
            'email' => $user->email, 'code' => $code, 'expires_at' => now()->subSecond(), 'verified' => false,
        ]])->post(route('password.verify_otp'), ['code' => $code])
            ->assertSessionHasErrors('email')->assertRedirect(route('password.request'))
            ->assertSessionMissing('password_reset');
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        $this->assertGuest();
    }

    public function test_unverified_reset_session_cannot_change_password(): void
    {
        $user = User::factory()->create();
        $this->requestCode($user);
        $this->get(route('password.reset_view'))->assertRedirect(route('password.request'));
        $this->post(route('password.update'), [
            'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('password.request'));
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        $this->assertGuest();
    }
}
