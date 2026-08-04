<?php

namespace App\Services\Admin;

use App\Jobs\SendUserInvitationEmail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function create(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => Hash::make(bin2hex(random_bytes(32))),
                'role' => $attributes['role'],
                'status' => 'active',
                'email_verified_at' => null,
            ]);

            $setupUrl = url(route('password.reset', [
                'token' => Password::broker()->createToken($user),
                'email' => $user->email,
            ], false));

            DB::afterCommit(fn () => SendUserInvitationEmail::dispatch(
                $user->name,
                $user->email,
                $user->role,
                $setupUrl,
            ));

            return $user;
        });
    }

    public function deleteWithOtp(User $actor, User $target, string $plainCode): void
    {
        DB::transaction(function () use ($actor, $target, $plainCode): void {
            $otp = Otp::query()
                ->where('user_id', $actor->id)
                ->where('email', $actor->email)
                ->where('purpose', Otp::PURPOSE_USER_DELETE)
                ->where('used', false)
                ->where('expires_at', '>', now())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otp || ! Hash::check($plainCode, $otp->otp_code)) {
                throw ValidationException::withMessages([
                    'otp_code' => 'Kode OTP tidak valid atau telah kedaluwarsa.',
                ]);
            }

            $otp->update(['used' => true]);
            $target->delete();
        });
    }
}