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
        // OTP registration gating requires the email to be verified in-session
        // first (see RegisteredUserController::store). The OTP itself is
        // verified by OtpController, which marks this flag.
        session(['registration_otp_verified_email' => 'test@example.com']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_requires_otp_email_verification(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'otp-gated@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertGuest();
    }
}

