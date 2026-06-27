<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Otp;
use App\Mail\OtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user is redirected to login/otp and an email is sent on correct credentials.
     */
    public function test_user_login_sends_otp_and_redirects_to_verification_page()
    {
        Mail::fake();

        // 1. Create a user
        $user = User::factory()->create([
            'email' => 'testuser@drasthalearning.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. Post credentials to login
        $response = $this->post('/login', [
            'email' => 'testuser@drasthalearning.com',
            'password' => 'password123',
        ]);

        // 3. Assert redirect to login/otp
        $response->assertRedirect('/login/otp');

        // 4. Assert user is NOT logged in (still a guest)
        $this->assertGuest();

        // 5. Assert OTP mail was sent
        Mail::assertSent(OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // 6. Assert OTP record was created in database
        $this->assertDatabaseHas('otps', [
            'email' => $user->email,
            'used' => false,
        ]);
    }

    /**
     * Test that accessing the login/otp page works when the session exists.
     */
    public function test_user_can_access_otp_form_with_valid_session()
    {
        $response = $this->withSession(['login_otp_email' => 'testuser@drasthalearning.com'])
            ->get('/login/otp');

        $response->assertStatus(200);
    }

    /**
     * Test that accessing the login/otp page redirects to login when no session is active.
     */
    public function test_user_cannot_access_otp_form_without_valid_session()
    {
        $response = $this->get('/login/otp');
        $response->assertRedirect('/login');
    }

    /**
     * Test that entering the correct OTP logs the user in.
     */
    public function test_entering_correct_otp_authenticates_user()
    {
        // 1. Create a user
        $user = User::factory()->create([
            'email' => 'testuser@drasthalearning.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. Generate OTP in database
        $otp = Otp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_code' => '654321',
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        // 3. Post correct OTP code
        $response = $this->withSession([
                'login_otp_email' => $user->email,
                'login_otp_remember' => false
            ])
            ->post('/login/otp', [
                'otp_code' => '654321',
            ]);

        // 4. Assert redirect to dashboard
        $response->assertRedirect('/dashboard');

        // 5. Assert user is authenticated
        $this->assertAuthenticatedAs($user);

        // 6. Assert OTP is marked as used
        $this->assertTrue($otp->fresh()->used);
    }

    /**
     * Test that entering an incorrect OTP code fails validation.
     */
    public function test_entering_incorrect_otp_fails_validation()
    {
        // 1. Create a user
        $user = User::factory()->create([
            'email' => 'testuser@drasthalearning.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. Generate OTP in database
        Otp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_code' => '654321',
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        // 3. Post incorrect OTP code
        $response = $this->withSession([
                'login_otp_email' => $user->email,
                'login_otp_remember' => false
            ])
            ->post('/login/otp', [
                'otp_code' => '000000',
            ]);

        // 4. Assert validation errors
        $response->assertSessionHasErrors('otp_code');

        // 5. Assert user is still a guest
        $this->assertGuest();
    }

    /**
     * Test that user can request a new OTP code to be sent.
     */
    public function test_user_can_resend_otp()
    {
        Mail::fake();

        // 1. Create a user
        $user = User::factory()->create([
            'email' => 'testuser@drasthalearning.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. Post resend OTP request
        $response = $this->withSession(['login_otp_email' => $user->email])
            ->post('/login/otp/resend');

        // 3. Assert back redirect
        $response->assertRedirect();
        $response->assertSessionHas('status');

        // 4. Assert new OTP mail was sent
        Mail::assertSent(OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // 5. Assert new OTP record is in database
        $this->assertDatabaseHas('otps', [
            'email' => $user->email,
            'used' => false,
        ]);
    }
}
