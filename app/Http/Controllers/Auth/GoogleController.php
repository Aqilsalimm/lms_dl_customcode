<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return RedirectResponse
     */
    public function redirectToGoogle()
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return view('auth.google-callback', [
                'authData' => [
                    'success' => false,
                    'error' => 'Google Client ID atau Client Secret belum di-konfigurasi di file .env!',
                    'redirect_url' => '/?login=true'
                ]
            ]);
        }
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return view('auth.google-callback', [
                'authData' => [
                    'success' => false,
                    'error' => 'Gagal masuk menggunakan Google. Silakan coba lagi.',
                    'redirect_url' => '/?login=true'
                ]
            ]);
        }

        // Search for user by google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            // Check if user exists by email (preventing duplicates and linking manual registration accounts)
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Link Google account to existing user
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'google_token' => $googleUser->token,
                ]);
            } else {
                // Create a new user
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'google_token' => $googleUser->token,
                    'role' => 'student',
                    'status' => 'active',
                    'password' => Hash::make(Str::random(24)),
                    'photo' => $googleUser->getAvatar(),
                ]);

                event(new Registered($user));
            }
        } else {
            // Update token if changed
            $user->update([
                'google_token' => $googleUser->token,
            ]);
        }

        // Limit Concurrent Login Sessions
        $limitSessions = \App\Models\Setting::where('key', 'limit_login_sessions')->value('value');
        if (filter_var($limitSessions, FILTER_VALIDATE_BOOLEAN)) {
            $hasActiveSession = \DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', session()->getId())
                ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime'))->getTimestamp())
                ->exists();

            if ($hasActiveSession) {
                return view('auth.google-callback', [
                    'authData' => [
                        'success' => false,
                        'error' => 'Akun Anda sedang aktif di perangkat lain. Silakan log out terlebih dahulu dari perangkat tersebut.',
                        'redirect_url' => '/?login=true'
                    ]
                ]);
            }
        }

        // --- ENFORCE OTP VERIFICATION FLOW ---
        // Store OTP details in the new guest session
        session([
            'login_otp_email' => $user->email,
            'login_otp_remember' => true,
        ]);

        // Generate a 6-digit OTP code (static '111111' in local mode, random otherwise)
        $code = app()->environment('local') ? 111111 : random_int(100000, 999999);

        // Store OTP in database
        \App\Models\Otp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_code' => (string) $code,
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        // Send OTP email
        Mail::to($user->email)->send(new \App\Mail\OtpMail($code));

        return view('auth.google-callback', [
            'authData' => [
                'success' => true,
                'user' => $user,
                'redirect_url' => route('login.otp', absolute: false)
            ]
        ]);
    }
}
