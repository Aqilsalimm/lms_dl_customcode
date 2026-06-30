<?php

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
        Mail::to($user->email)->send(new OtpMail($code));

        return redirect()->route('login.otp');
    }

    /**
     * Display the login OTP verification view.
     */
    public function showOtpForm(Request $request): Response|RedirectResponse
    {
        if (!session()->has('login_otp_email')) {
            return redirect()->route('login');
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
            return redirect()->route('login');
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
            return redirect()->route('login');
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
