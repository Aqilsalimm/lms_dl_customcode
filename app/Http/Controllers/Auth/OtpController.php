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

class OtpController extends \App\Http\Controllers\Controller
{
    /**
     * Send OTP to given email (used after registration step 1)
     */
    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        // Generate a 6‑digit code
        $code = random_int(100000, 999999);

        // Store OTP
        Otp::create([
            'user_id'   => $user?->id,
            'email'     => $request->email,
            'otp_code'  => (string) $code,
            'expires_at'=> Carbon::now()->addMinutes(10),
            'used'      => false,
        ]);

        // Send email (via configured mailer - Brevo SMTP)
        Mail::to($request->email)->send(new OtpMail($code));

        // Return back with a flag so front‑end can show the alert
        return redirect()->back()->with('otp_sent', true);
    }

    /**
     * Verify OTP entered by the user
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'otp_code' => 'required|digits:6',
        ]);

        $otp = Otp::where('email', $request->email)
                  ->where('otp_code', $request->otp_code)
                  ->where('used', false)
                  ->where(function ($q) {
                      $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                  })
                  ->first();

        if (!$otp) {
            return redirect()->back()->withErrors(['otp_code' => 'Kode OTP tidak valid atau telah kadaluarsa.']);
        }

        // Mark as used
        $otp->update(['used' => true]);

        // Log in the user
        $user = User::find($otp->user_id);
        if ($user) {
            auth()->login($user);
        }

        // Redirect to dashboard
        return redirect()->route('dashboard');
    }
}
?>
