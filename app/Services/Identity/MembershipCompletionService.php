<?php

namespace App\Services\Identity;

use App\Models\OrganizationMembership;
use Illuminate\Support\Facades\DB;

class MembershipCompletionService
{
    /**
     * Check if an organization membership and user profile meet all completion criteria.
     */
    public function isComplete(OrganizationMembership $membership): bool
    {
        $membership->loadMissing(['user.profile', 'organization']);

        if (!$membership->is_active || !$membership->organization || !$membership->organization->is_active) {
            return false;
        }

        $user = $membership->user;
        if (!$user || !$user->profile || empty(trim((string) $user->profile->legal_name))) {
            return false;
        }

        if (empty(trim((string) $membership->member_type))) {
            return false;
        }

        if (empty(trim((string) $membership->member_number))) {
            return false;
        }

        return true;
    }

    /**
     * Update user profile & membership data, then calculate and update profile_completed_at timestamp.
     */
    public function updateAndEvaluate(OrganizationMembership $membership, array $profileData, array $membershipData): bool
    {
        return DB::transaction(function () use ($membership, $profileData, $membershipData) {
            $user = $membership->user;

            if ($user) {
                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    array_filter([
                        'legal_name' => $profileData['legal_name'] ?? null,
                        'phone' => $profileData['phone'] ?? null,
                        'gender' => $profileData['gender'] ?? null,
                    ], fn ($val) => $val !== null)
                );
            }

            $membership->update(array_filter([
                'member_type' => $membershipData['member_type'] ?? null,
                'member_number' => $membershipData['member_number'] ?? null,
                'division' => $membershipData['division'] ?? null,
                'position' => $membershipData['position'] ?? null,
            ], fn ($val) => $val !== null));

            return $this->evaluateAndPersist($membership);
        });
    }

    /**
     * Evaluate completion status and persist profile_completed_at timestamp.
     */
    public function evaluateAndPersist(OrganizationMembership $membership): bool
    {
        $isNowComplete = $this->isComplete($membership->fresh(['user.profile', 'organization']));

        if ($isNowComplete) {
            if (!$membership->profile_completed_at) {
                $membership->update(['profile_completed_at' => now()]);
            }
        } else {
            if ($membership->profile_completed_at) {
                $membership->update(['profile_completed_at' => null]);
            }
        }

        return $isNowComplete;
    }
}
