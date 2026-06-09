<?php

namespace App\Services;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;
use Exception;

class MidtransService
{
    public function __construct()
    {
        $serverKey = \App\Models\Setting::where('key', 'midtrans_server_key')->value('value');
        $sandboxMode = \App\Models\Setting::where('key', 'midtrans_sandbox_mode')->value('value');
        
        if (empty($serverKey)) {
            $serverKey = config('midtrans.server_key') ?: 'SB-Mid-server-placeholder';
        }
        
        $isProduction = false;
        if ($sandboxMode !== null) {
            $isProduction = !filter_var($sandboxMode, FILTER_VALIDATE_BOOLEAN);
        } else {
            $isProduction = config('midtrans.is_production', false);
        }

        Config::$serverKey = $serverKey;
        Config::$isProduction = $isProduction;
        Config::$isSanitized = config('midtrans.is_sanitized', true);
        Config::$is3ds = config('midtrans.is_3ds', true);
    }

    /**
     * Create snap token for payment
     */
    public function createSnapToken(Order $order): ?string
    {
        $params = [
            'transaction_details' => [
                'order_id' => 'DRSTH-' . $order->id . '-' . time(),
                'gross_amount' => (int) $order->amount,
            ],
            'customer_details' => [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'item_details' => [
                [
                    'id' => $order->buyable_id,
                    'price' => (int) $order->amount,
                    'quantity' => 1,
                    'name' => $order->buyable->title ?? 'LMS Course/Bundle Purchase',
                ]
            ]
        ];

        try {
            // Check if server key is placeholder
            if (str_contains(Config::$serverKey, 'placeholder')) {
                // If placeholder, return a mock token for development testing!
                return 'MOCK-SNAP-TOKEN-' . uniqid();
            }

            return Snap::getSnapToken($params);
        } catch (Exception $e) {
            logger()->error('Midtrans Snap Error: ' . $e->getMessage());
            // Fallback for development if Midtrans API fails or credentials are unset
            return 'MOCK-SNAP-TOKEN-' . uniqid();
        }
    }
}
