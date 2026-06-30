<?php
 
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset OTP request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Alamat email tidak terdaftar di sistem kami.'],
            ]);
        }

        // 60-second cooldown check to prevent abuse
        $lastOtp = Otp::where('email', $request->email)
            ->latest()
            ->first();

        if ($lastOtp && $lastOtp->created_at->addSeconds(60)->isFuture()) {
            $secondsLeft = 60 - now()->diffInSeconds($lastOtp->created_at);
            throw ValidationException::withMessages([
                'email' => ["Silakan tunggu {$secondsLeft} detik sebelum meminta kode OTP kembali."],
            ]);
        }

        // Generate a 6-digit OTP code (use static 111111 in local environment)
        $code = app()->environment('local') ? 111111 : random_int(100000, 999999);

        // Store OTP in database (valid for 5 minutes)
        Otp::create([
            'user_id' => $user->id,
            'email' => $request->email,
            'otp_code' => (string) $code,
            'expires_at' => now()->addMinutes(5),
            'used' => false,
        ]);

        // Send OTP email
        Mail::to($request->email)->send(new OtpMail($code));

        // Save email to session for the next step
        session(['reset_password_email' => $request->email]);

        return redirect()->route('password.otp')->with('status', 'Kode OTP telah berhasil dikirim ke alamat email Anda.');
    }

    /**
     * Display the forgot password OTP entry form.
     */
    public function showOtpForm(): Response|RedirectResponse
    {
        if (!session()->has('reset_password_email')) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/ForgotPasswordOtp', [
            'email' => session('reset_password_email'),
            'status' => session('status'),
        ]);
    }

    /**
     * Verify the forgot password OTP.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);

        $email = session('reset_password_email');

        if (!$email) {
            return redirect()->route('password.request');
        }

        $otp = Otp::where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        $attempts = session('reset_otp_failed_attempts', 0);

        if (!$otp || $otp->otp_code !== $request->otp_code) {
            $attempts++;
            session(['reset_otp_failed_attempts' => $attempts]);

            if ($attempts >= 5) {
                // Invalidate active OTPs for this email due to lockout
                Otp::where('email', $email)->update(['used' => true]);
                session()->forget(['reset_password_email', 'reset_otp_failed_attempts']);

                return redirect()->route('password.request')->with('status', 'Terlalu banyak percobaan yang salah. Silakan ajukan ulang permintaan lupa password.');
            }

            $remaining = 5 - $attempts;
            throw ValidationException::withMessages([
                'otp_code' => ["Kode OTP tidak valid atau telah kadaluarsa. Sisa percobaan: {$remaining} kali."],
            ]);
        }

        // Mark OTP as used
        $otp->update(['used' => true]);

        // Clear failed attempts counter
        session()->forget('reset_otp_failed_attempts');

        // Generate a secure one-time session-bound verification token
        $token = Str::random(40);
        session([
            'reset_password_token' => $token,
            'reset_password_verified_at' => now()->timestamp,
        ]);

        return redirect()->route('password.reset', ['token' => $token, 'email' => $email]);
    }

    /**
     * Resend the forgot password OTP.
     */
    public function resendOtp(Request $request): RedirectResponse
    {
        $email = session('reset_password_email');

        if (!$email) {
            return redirect()->route('password.request');
        }

        // 60-second cooldown check to prevent abuse
        $lastOtp = Otp::where('email', $email)
            ->latest()
            ->first();

        if ($lastOtp && $lastOtp->created_at->addSeconds(60)->isFuture()) {
            $secondsLeft = 60 - now()->diffInSeconds($lastOtp->created_at);
            return redirect()->back()->with('status', "Silakan tunggu {$secondsLeft} detik sebelum meminta kode OTP kembali.");
        }

        $user = User::where('email', $email)->firstOrFail();

        // Generate new OTP (static 111111 in local mode)
        $code = app()->environment('local') ? 111111 : random_int(100000, 999999);

        // Store OTP in database (valid for 5 minutes)
        Otp::create([
            'user_id' => $user->id,
            'email' => $email,
            'otp_code' => (string) $code,
            'expires_at' => now()->addMinutes(5),
            'used' => false,
        ]);

        // Send OTP email
        Mail::to($email)->send(new OtpMail($code));

        // Reset failed attempts counter for new code
        session()->forget('reset_otp_failed_attempts');

        return redirect()->back()->with('status', 'Kode OTP baru telah berhasil dikirim ke alamat email Anda.');
    }
}
