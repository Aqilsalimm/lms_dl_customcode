<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // 1. Validate Fraud Protection (reCAPTCHA)
        $fraudEnabled = \App\Models\Setting::getValue('fraud_protection_enabled');
        if (filter_var($fraudEnabled, FILTER_VALIDATE_BOOLEAN)) {
            $method = \App\Models\Setting::getValue('fraud_protection_method');
            if (in_array($method, ['recaptcha_v2', 'recaptcha_v3'])) {
                $secret = \App\Models\Setting::getValue('recaptcha_secret_key');
                $gResponse = $this->input('g-recaptcha-response');
                if ($secret && $gResponse) {
                    $verify = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$gResponse}");
                    if ($verify) {
                        $captchaResponse = json_decode($verify);
                        if (!isset($captchaResponse->success) || !$captchaResponse->success) {
                            throw ValidationException::withMessages([
                                'email' => 'reCAPTCHA verification failed. Please try again.',
                            ]);
                        }
                    }
                }
            }
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // 2. Limit Concurrent Login Sessions
        $limitSessions = \App\Models\Setting::getValue('limit_login_sessions');
        if (filter_var($limitSessions, FILTER_VALIDATE_BOOLEAN)) {
            $user = Auth::user();
            if (config('session.driver') === 'database' && \Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                $hasActiveSession = \Illuminate\Support\Facades\DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->where('id', '!=', session()->getId())
                    ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime', 120))->getTimestamp())
                    ->exists();

                if ($hasActiveSession) {
                    Auth::logout();
                    throw ValidationException::withMessages([
                        'email' => 'Akun Anda sedang aktif di perangkat lain. Silakan log out terlebih dahulu dari perangkat tersebut.',
                    ]);
                }
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
