<?php

namespace App\Services\Certificates;

use App\Models\Enrollment;
use App\Models\User;

class CertificateIdentitySnapshotFactory
{
    /**
     * Create a versioned identity snapshot payload for certificate issuance.
     */
    public function create(User $user, ?Enrollment $enrollment = null): array
    {
        $user->loadMissing('profile');

        $legalName = $user->profile?->legal_name;
        $displayName = $user->name;

        $snapshot = [
            'version' => 1,
            'recipient' => [
                'user_id' => $user->id,
                'display_name' => $displayName,
                'legal_name' => !empty(trim((string) $legalName)) ? $legalName : $displayName,
                'email' => $user->email,
            ],
            'organization' => null,
            'membership' => null,
            'issued_at' => now()->toIso8601String(),
        ];

        if ($enrollment && $enrollment->organization_membership_id) {
            $enrollment->loadMissing('organizationMembership.organization');
            $membership = $enrollment->organizationMembership;

            if ($membership && $membership->organization) {
                $org = $membership->organization;

                $snapshot['organization'] = [
                    'organization_id' => $org->id,
                    'code' => $org->code,
                    'name' => $org->name,
                    'legal_name' => $org->legal_name,
                ];

                $snapshot['membership'] = [
                    'membership_id' => $membership->id,
                    'member_type' => $membership->member_type,
                    'member_number' => $membership->member_number,
                    'division' => $membership->division,
                    'position' => $membership->position,
                ];
            }
        }

        return $snapshot;
    }
}
