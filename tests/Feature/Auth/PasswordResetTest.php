<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Otp;
use App\Mail\OtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_otp_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Mail::assertSent(OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
        
        $this->assertDatabaseHas('otps', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_screen_can_be_rendered_with_valid_session(): void
    {
        $user = User::factory()->create();
        
        $response = $this->withSession([
            'reset_password_token' => 'dummy_token',
            'reset_password_email' => $user->email,
            'reset_password_verified_at' => time(),
        ])->get('/reset-password/dummy_token');

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_session(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession([
            'reset_password_token' => 'dummy_token',
            'reset_password_email' => $user->email,
            'reset_password_verified_at' => time(),
        ])->post('/reset-password', [
            'token' => 'dummy_token',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/dashboard');
    }
}
