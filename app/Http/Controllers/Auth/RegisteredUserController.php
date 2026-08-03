<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:student,instructor',
            'photo' => 'nullable|image|max:1024',
        ], [
            'photo.max' => 'Ukuran file foto profil maksimal adalah 1MB.',
        ]);

        if (config('auth.features.otp_registration', true)) {
            $verifiedEmail = $request->session()->get('registration_otp_verified_email');
            if ($verifiedEmail !== $request->email) {
                throw ValidationException::withMessages([
                    'otp' => __('Tolong verifikasi email Anda dengan OTP terlebih dahulu sebelum mendaftar.'),
                ]);
            }
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profile-photos', 'public');
            \App\Services\ImageOptimizer::optimize(
                storage_path('app/public/' . $photoPath),
                $request->file('photo')->getMimeType()
            );
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->role === 'instructor' ? 'pending' : 'active',
            'photo' => $photoPath ? $photoPath : null,
        ]);

        event(new Registered($user));

        if (config('auth.features.otp_registration', true)) {
            $request->session()->forget('registration_otp_verified_email');
        }

        Auth::login($user);

        if ($user->role === 'instructor') {
            return redirect()->route('instructor.profile.setup');
        }

        return redirect(route('dashboard', absolute: false));
    }
}
