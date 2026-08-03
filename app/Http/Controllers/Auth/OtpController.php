<?php

namespace App\Http\Controllers\Auth;

use App\Models\Otp;
use App\Models\User;
use App\Mail\OtpMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the e-mail ownership verification step of the registration flow
 * (Register.vue step 1 -> step 2 -> step 3).
 *
 * SECURITY: this controller must never authenticate anybody. It only proves
 * that the visitor controls the mailbox they typed in. The actual account is
 * created — and the session authenticated — by RegisteredUserController::store.
 */
class OtpController extends \App\Http\Controllers\Controller
{
    /**
     * Send OTP to the given email and bind that email to the current session.
     */
    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->email));

        // Invalidate any outstanding codes for this address so that only the
        // newest code can ever be redeemed (previously every code sent stayed
        // valid for 10 minutes, widening the brute-force window per request).
        Otp::where('email', $email)
            ->where('used', false)
            ->update(['used' => true]);

        $code = random_int(100000, 999999);

        Otp::create([
            'user_id'    => null,
            'email'      => $email,
            'otp_code'   => (string) $code,
            'expires_at' => Carbon::now()->addMinutes(10),
            'used'       => false,
        ]);

        // Bind the pending email to the session. verify() reads the address
        // from here instead of trusting the request body, so an attacker
        // cannot submit somebody else's email with a guessed code.
        $request->session()->put('registration_otp_email', $email);
        $request->session()->forget('registration_otp_verified_email');

        Mail::to($email)->send(new OtpMail($code));

        return redirect()->back()->with('otp_sent', true);
    }

    /**
     * Verify the OTP for the email bound to this session.
     *
     * On success the email is marked verified in the session; it does NOT log
     * anybody in. RegisteredUserController::store consumes the flag.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);

        $email = $request->session()->get('registration_otp_email');

        if (!$email) {
            return redirect()->back()->withErrors([
                'otp_code' => 'Sesi verifikasi tidak ditemukan. Silakan kirim ulang kode OTP.',
            ]);
        }

        $otp = Otp::where('email', $email)
                  ->where('otp_code', $request->otp_code)
                  ->where('used', false)
                  ->where(function ($q) {
                      $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                  })
                  ->latest('id')
                  ->first();

        if (!$otp) {
            return redirect()->back()->withErrors([
                'otp_code' => 'Kode OTP tidak valid atau telah kadaluarsa.',
            ]);
        }

        $otp->update(['used' => true]);

        $request->session()->put('registration_otp_verified_email', $email);
        $request->session()->forget('registration_otp_email');

        return redirect()->back()->with('otp_verified', true);
    }
}
