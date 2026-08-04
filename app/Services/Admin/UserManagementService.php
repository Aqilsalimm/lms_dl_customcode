<?php

namespace App\Services\Admin;

use App\Jobs\SendUserInvitationEmail;
use App\Mail\AccountDeactivatedMail;
use App\Models\Otp;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    public function deleteUser(User $actor, User $target, ?string $customMessage = null): void
    {
        DB::transaction(function () use ($actor, $target, $customMessage): void {

            $targetName = $target->name;
            $targetEmail = $target->email;

            $target->delete();

            // Silent Delete toggle (Settings LMS -> Course Settings):
            // ON  = penonaktifan diam-diam, tanpa notifikasi email ke pengguna.
            // OFF = kirim notifikasi email penonaktifan ke pengguna.
            $silentDelete = filter_var(
                Setting::getValue('user_silent_delete', 'false'),
                FILTER_VALIDATE_BOOLEAN
            );

            if (! $silentDelete && $targetEmail) {
                DB::afterCommit(fn () => Mail::to($targetEmail)->queue(
                    new AccountDeactivatedMail($targetName, $customMessage)
                ));
            }
        });
    }
}
