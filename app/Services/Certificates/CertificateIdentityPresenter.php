<?php

namespace App\Services\Certificates;

use App\Models\UserCertificate;

class CertificateIdentityPresenter
{
    /**
     * Present the formatted recipient name for a certificate.
     */
    public function getRecipientName(UserCertificate $userCert): string
    {
        $snapshot = $userCert->identity_snapshot;

        if (is_array($snapshot) && isset($snapshot['recipient'])) {
            $legalName = $snapshot['recipient']['legal_name'] ?? null;
            $displayName = $snapshot['recipient']['display_name'] ?? null;

            if (!empty(trim((string) $legalName))) {
                return $legalName;
            }
            if (!empty(trim((string) $displayName))) {
                return $displayName;
            }
        }

        $userCert->loadMissing('user');
        return $userCert->user ? $userCert->user->name : 'Peserta';
    }

    /**
     * Present organization name from snapshot.
     */
    public function getOrganizationName(UserCertificate $userCert): ?string
    {
        $snapshot = $userCert->identity_snapshot;
        return $snapshot['organization']['name'] ?? null;
    }

    /**
     * Present member number from snapshot, with optional privacy masking.
     */
    public function getMemberNumber(UserCertificate $userCert, bool $masked = true): ?string
    {
        $snapshot = $userCert->identity_snapshot;
        $num = $snapshot['membership']['member_number'] ?? null;

        if (empty($num)) {
            return null;
        }

        if (!$masked || strlen($num) <= 4) {
            return $num;
        }

        $prefix = substr($num, 0, 2);
        $suffix = substr($num, -2);
        $middle = str_repeat('*', max(2, strlen($num) - 4));

        return $prefix . $middle . $suffix;
    }

    /**
     * Present division from snapshot.
     */
    public function getDivision(UserCertificate $userCert): ?string
    {
        $snapshot = $userCert->identity_snapshot;
        return $snapshot['membership']['division'] ?? null;
    }

    /**
     * Present position from snapshot.
     */
    public function getPosition(UserCertificate $userCert): ?string
    {
        $snapshot = $userCert->identity_snapshot;
        return $snapshot['membership']['position'] ?? null;
    }

    /**
     * Build presenter array payload for Inertia views.
     */
    public function present(UserCertificate $userCert, bool $masked = true): array
    {
        $isLegacy = empty($userCert->identity_snapshot);

        return [
            'recipient_name' => $this->getRecipientName($userCert),
            'organization_name' => $this->getOrganizationName($userCert),
            'member_number' => $this->getMemberNumber($userCert, $masked),
            'division' => $this->getDivision($userCert),
            'position' => $this->getPosition($userCert),
            'is_legacy_identity_fallback' => $isLegacy,
        ];
    }
}
