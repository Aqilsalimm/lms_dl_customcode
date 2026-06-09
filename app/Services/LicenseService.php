<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class LicenseService
{
    protected string $apiUrl = 'https://licensing.drastha.com/api/v1/verify';

    /**
     * Get unique hardware/server fingerprint
     */
    public function getFingerprint(): string
    {
        $data = [
            PHP_OS,
            gethostname(),
            phpversion(),
            config('database.connections.mysql.database', 'default_db')
        ];
        return hash('sha256', implode('|', $data));
    }

    /**
     * Verify license with local cache support (24-48 hour validity)
     */
    public function isValid(): bool
    {
        $licenseKey = Setting::where('key', 'license_key')->value('value');
        if (empty($licenseKey)) {
            return false;
        }

        // 1. Owner Developer Bypass key (Instant lifetime offline unlock)
        if ($licenseKey === 'DRSTHA-DEVELOPER-BYPASS-9999') {
            return true;
        }

        // 2. Owner Lifetime Master key (Offline Signature validation)
        // Format: DRSTHA-MASTER-LIFETIME-[MD5_OF_DOMAIN_WITH_SALT]
        if (str_starts_with($licenseKey, 'DRSTHA-MASTER-LIFETIME-')) {
            $providedSignature = str_replace('DRSTHA-MASTER-LIFETIME-', '', $licenseKey);
            $expectedSignature = strtoupper(md5(request()->getHost() . '-drastha-secure-salt-2026'));
            if ($providedSignature === $expectedSignature) {
                return true;
            }
        }

        // Cache verification status for commercial licenses to prevent page load lag
        return Cache::remember('drl_license_status', now()->addHours(24), function () use ($licenseKey) {
            return $this->verifyRemote($licenseKey);
        });
    }

    /**
     * Directly verify license key from remote central license server
     */
    public function verifyRemote(string $licenseKey): bool
    {
        // Owner Developer Bypass & Master verification is already handled locally in isValid()
        if ($licenseKey === 'DRSTHA-DEVELOPER-BYPASS-9999') {
            return true;
        }
        if (str_starts_with($licenseKey, 'DRSTHA-MASTER-LIFETIME-')) {
            $providedSignature = str_replace('DRSTHA-MASTER-LIFETIME-', '', $licenseKey);
            $expectedSignature = strtoupper(md5(request()->getHost() . '-drastha-secure-salt-2026'));
            return $providedSignature === $expectedSignature;
        }

        try {
            $response = Http::timeout(5)->post($this->apiUrl, [
                'license_key' => $licenseKey,
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
                'fingerprint' => $this->getFingerprint(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['valid']) && $data['valid'] === true;
            }
        } catch (\Exception $e) {
            // Keep previous status cached if central API is temporarily offline
            return Cache::get('drl_license_status', false);
        }

        return false;
    }
}
