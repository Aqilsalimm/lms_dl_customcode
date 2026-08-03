<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\Otp;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Check if Email Verification is enforced
        $emailVerifyEnabled = filter_var(\App\Models\Setting::getValue('email_verification_enabled') ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $needsEmailVerification = $emailVerifyEnabled && is_null($user->email_verified_at);

        // Check if 2FA is enabled dynamically from settings table
        $twoFactorEnabled = filter_var(\App\Models\Setting::getValue('two_factor_auth_enabled') ?? 'false', FILTER_VALIDATE_BOOLEAN);

        $twoFactorLocationsRaw = \App\Models\Setting::getValue('two_factor_auth_locations');
        $twoFactorLocations = [];
        if ($twoFactorLocationsRaw) {
            try {
                $twoFactorLocations = is_array($twoFactorLocationsRaw) ? $twoFactorLocationsRaw : (json_decode($twoFactorLocationsRaw, true) ?? []);
            } catch (\Exception $e) {}
        }

        // Map user role group (Settings UI uses 'admin', 'tutor', 'student', 'all')
        // DB roles are 'admin', 'instructor', 'student'
        $userRoleMapped = $user->role;
        if ($userRoleMapped === 'instructor') {
            $userRoleMapped = 'tutor';
        }

        $shouldTriggerTwoFactor = $twoFactorEnabled && (
            empty($twoFactorLocations) || 
            in_array($userRoleMapped, $twoFactorLocations) || 
            in_array('all', $twoFactorLocations) ||
            in_array('admin_login', $twoFactorLocations) && $userRoleMapped === 'admin' ||
            in_array('tutor_login', $twoFactorLocations) && $userRoleMapped === 'tutor' ||
            in_array('student_login', $twoFactorLocations) && $userRoleMapped === 'student'
        );

        if ($shouldTriggerTwoFactor || $needsEmailVerification) {
            // Enforce OTP verification by logging out immediately
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Store OTP details in the new guest session
            session([
                'login_otp_email' => $user->email,
                'login_otp_remember' => $request->boolean('remember'),
            ]);

            // Generate a 6-digit OTP code (static '111111' in local mode, random otherwise)
            $code = app()->environment('local') ? 111111 : random_int(100000, 999999);

            // Store OTP in database
            Otp::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'otp_code' => (string) $code,
                'expires_at' => now()->addMinutes(10),
                'used' => false,
            ]);

            // Send OTP email
            try {
                Mail::to($user->email)->send(new OtpMail($code));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send OTP email: ' . $e->getMessage());
            }

            return new RedirectResponse(route('login.otp', absolute: false));
        }

        // If 2FA is disabled or doesn't apply to this user's role:
        // Regenerate session and proceed directly to dashboard
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Display the login OTP verification view.
     */
    public function showOtpForm(Request $request): Response|RedirectResponse
    {
        if (!session()->has('login_otp_email')) {
            return new RedirectResponse(route('login', absolute: false));
        }

        return Inertia::render('Auth/LoginOtp', [
            'email' => session('login_otp_email'),
            'status' => session('status'),
        ]);
    }

    /**
     * Verify the login OTP.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);

        $email = session('login_otp_email');
        if (!$email) {
            return new RedirectResponse(route('login', absolute: false));
        }

        $otp = Otp::where('email', $email)
            ->where('otp_code', $request->otp_code)
            ->where('used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$otp) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'otp_code' => 'Kode OTP tidak valid atau telah kadaluarsa.',
            ]);
        }

        // Mark OTP as used
        $otp->update(['used' => true]);

        // Log the user in
        $user = User::where('email', $email)->firstOrFail();

        if (is_null($user->email_verified_at)) {
            $user->update(['email_verified_at' => now()]);
        }

        Auth::login($user, session('login_otp_remember', false));

        // Clean up temporary session data
        $request->session()->regenerate();
        session()->forget(['login_otp_email', 'login_otp_remember']);

        return redirect()->intended(route('dashboard', absolute: false))->with('success', 'Selamat datang kembali!');
    }

    /**
     * Resend the login OTP.
     */
    public function resendOtp(Request $request): RedirectResponse
    {
        $email = session('login_otp_email');
        if (!$email) {
            return new RedirectResponse(route('login', absolute: false));
        }

        $user = User::where('email', $email)->firstOrFail();

        // Generate new OTP (static '111111' in local mode, random otherwise)
        $code = app()->environment('local') ? 111111 : random_int(100000, 999999);

        Otp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_code' => (string) $code,
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        // Send email
        Mail::to($user->email)->send(new OtpMail($code));

        return redirect()->back()->with('status', 'Kode OTP baru telah berhasil dikirim ke email Anda.');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')->with('logout_message', 'Anda telah berhasil keluar dari akun Anda. Silakan masuk kembali dengan menggunakan akun terdaftar sebelumnya untuk melanjutkan aktivitas belajar Anda.');
    }
}
