<?php

namespace App\Services\Payment;

use App\Models\Setting;

class MidtransConfig
{
    /**
     * Get Midtrans Server Key
     *
     * @return string|null
     */
    public static function serverKey(): ?string
    {
        return Setting::getValue('midtrans_server_key') ?: config('midtrans.server_key');
    }

    /**
     * Get Midtrans Client Key
     *
     * @return string|null
     */
    public static function clientKey(): ?string
    {
        return Setting::getValue('midtrans_client_key') ?: config('midtrans.client_key');
    }

    /**
     * Check if Midtrans is in Sandbox mode
     *
     * @return bool
     */
    public static function isSandbox(): bool
    {
        return config('midtrans.is_production', false) === false;
    }

    /**
     * Check if Midtrans is configured properly
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        $serverKey = self::serverKey();
        return !empty($serverKey) && !str_contains($serverKey, 'placeholder');
    }
}
