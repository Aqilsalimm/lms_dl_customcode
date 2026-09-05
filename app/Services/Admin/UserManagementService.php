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

            $occupation = match ($attributes['affiliation_type'] ?? 'Lainnya') {
                'Pemerintah' => 'PNS',
                'Pendidikan' => 'Mahasiswa',
                'Swasta' => 'Karyawan Swasta',
                default => 'Freelance',
            };

            \App\Models\UserProfile::create([
                'user_id' => $user->id,
                'occupation' => $occupation,
            ]);

            if (!empty($attributes['organization_name']) && ($attributes['affiliation_type'] ?? '') !== 'Lainnya') {
                $orgName = ucwords(strtolower(trim($attributes['organization_name'])));
                $orgCode = strtoupper(\Illuminate\Support\Str::slug($orgName)) . '-' . rand(1000, 9999);

                $organization = \App\Models\Organization::firstOrCreate(
                    ['name' => $orgName],
                    ['code' => $orgCode, 'is_active' => true]
                );

                \App\Models\OrganizationMembership::create([
                    'user_id' => $user->id,
                    'organization_id' => $organization->id,
                    'member_type' => 'other',
                    'is_active' => true,
                ]);
            }

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

    public function deleteUser(User $actor, User $target, ?string $customMessage = null, ?string $otpCode = null): void
    {
        $otpCode = $otpCode ?? request('otp_code');

        DB::transaction(function () use ($actor, $target, $customMessage, $otpCode): void {
            if ($otpCode) {
                $otps = \App\Models\Otp::where('user_id', $actor->id)
                    ->where('purpose', \App\Models\Otp::PURPOSE_USER_DELETE)
                    ->where('used', false)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })
                    ->get();

                foreach ($otps as $otp) {
                    if (\Illuminate\Support\Facades\Hash::check($otpCode, $otp->otp_code) || $otp->otp_code === $otpCode) {
                        $otp->update(['used' => true]);
                        break;
                    }
                }
            }

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
                DB::afterCommit(fn () => Mail::to($targetEmail)->send(
                    new AccountDeactivatedMail($targetName, $customMessage)
                ));
            }
        });
    }
}
