<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    /**
     * Get VAPID public key.
     */
    public function key(): JsonResponse
    {
        return response()->json([
            'publicKey' => config('webpush.vapid.public_key', env('VAPID_PUBLIC_KEY'))
        ]);
    }

    /**
     * Store or update user push subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
        ]);

        $endpoint = $request->endpoint;
        $key = $request->input('keys.p256dh');
        $token = $request->input('keys.auth');

        $user = $request->user();
        if ($user) {
            $user->updatePushSubscription($endpoint, $key, $token);
        }

        return response()->json(['message' => 'Subscription saved successfully.']);
    }

    /**
     * Delete push subscription.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        $user = $request->user();
        if ($user) {
            $user->deletePushSubscription($request->endpoint);
        }

        return response()->json(['message' => 'Subscription deleted successfully.']);
    }
}
