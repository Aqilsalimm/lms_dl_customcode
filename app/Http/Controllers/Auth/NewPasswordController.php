<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $token = $request->route('token');
        $sessionToken = session('reset_password_token');
        $sessionEmail = session('reset_password_email');
        $verifiedAt = session('reset_password_verified_at');

        // Tighten security by verifying session token, email, and 10-minute validity (600 seconds)
        if (!$sessionToken || $sessionToken !== $token || !$sessionEmail || !$verifiedAt || (time() - (int)$verifiedAt) > 600) {
            session()->forget(['reset_password_token', 'reset_password_email', 'reset_password_verified_at']);
            return redirect()->route('password.request')->with('status', 'Token verifikasi tidak valid atau telah kadaluarsa. Silakan ajukan ulang permintaan.');
        }

        return Inertia::render('Auth/ResetPassword', [
            'email' => $sessionEmail,
            'token' => $token,
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $sessionToken = session('reset_password_token');
        $sessionEmail = session('reset_password_email');
        $verifiedAt = session('reset_password_verified_at');

        // Security check on form submission
        if (!$sessionToken || $sessionToken !== $request->token || !$sessionEmail || $sessionEmail !== $request->email || !$verifiedAt || (time() - (int)$verifiedAt) > 600) {
            session()->forget(['reset_password_token', 'reset_password_email', 'reset_password_verified_at']);
            throw ValidationException::withMessages([
                'email' => ['Permintaan reset password tidak valid atau telah kadaluarsa. Silakan ajukan ulang.'],
            ]);
        }

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('email', $request->email)->firstOrFail();
        
        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        // Clean up temporary session data
        session()->forget(['reset_password_token', 'reset_password_email', 'reset_password_verified_at']);

        // Log the user in directly
        Auth::login($user);
        
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false))
            ->with('success', 'Password Anda berhasil diperbarui. Anda telah masuk ke akun Anda.');
    }
}
