<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Bundle;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected MidtransService $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Checkout page / action to initialize order
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'buyable_type' => 'required|string|in:course,bundle',
            'buyable_id' => 'required|integer',
        ]);

        $user = auth()->user();

        // Get buyable item
        if ($request->buyable_type === 'course') {
            $item = Course::findOrFail($request->buyable_id);
            $buyableClass = Course::class;
        } else {
            $item = Bundle::findOrFail($request->buyable_id);
            $buyableClass = Bundle::class;
        }

        // Check if already enrolled
        $isEnrolled = Enrollment::where('user_id', $user->id)
            ->where(function($query) use ($request) {
                if ($request->buyable_type === 'course') {
                    $query->where('course_id', $request->buyable_id);
                } else {
                    $query->where('bundle_id', $request->buyable_id);
                }
            })->exists();

        if ($isEnrolled) {
            return back()->with('error', 'Anda sudah terdaftar di kelas/bundle ini.');
        }

        $autoCompleteOrders = filter_var(
            \App\Models\Setting::where('key', 'auto_complete_ecommerce_orders')->value('value'),
            FILTER_VALIDATE_BOOLEAN
        );

        // Create Order
        $order = Order::create([
            'user_id' => $user->id,
            'buyable_type' => $buyableClass,
            'buyable_id' => $item->id,
            'amount' => $item->price,
            'status' => $autoCompleteOrders ? 'completed' : 'pending',
            'payment_type' => $autoCompleteOrders ? 'auto_complete' : null,
        ]);

        if ($autoCompleteOrders) {
            // Enroll User
            if ($order->buyable_type === Course::class) {
                Enrollment::firstOrCreate([
                    'user_id' => $order->user_id,
                    'course_id' => $order->buyable_id,
                ], [
                    'enrolled_at' => now(),
                ]);
            } elseif ($order->buyable_type === Bundle::class) {
                $bundle = Bundle::find($order->buyable_id);
                if ($bundle) {
                    Enrollment::firstOrCreate([
                        'user_id' => $order->user_id,
                        'bundle_id' => $bundle->id,
                    ], [
                        'enrolled_at' => now(),
                    ]);

                    foreach ($bundle->courses as $course) {
                        Enrollment::firstOrCreate([
                            'user_id' => $order->user_id,
                            'course_id' => $course->id,
                        ], [
                            'enrolled_at' => now(),
                        ]);
                    }
                }
            }

            return response()->json([
                'completed' => true,
                'order_id' => $order->id
            ]);
        }

        // Get Snap Token
        $snapToken = $this->midtransService->createSnapToken($order);
        $order->update(['snap_token' => $snapToken]);

        return response()->json([
            'snap_token' => $snapToken,
            'order_id' => $order->id
        ]);
    }

    /**
     * Midtrans Notification / Webhook callback
     */
    public function notification(Request $request)
    {
        $payload = $request->all();
        logger()->info('Midtrans Notification Payload: ', $payload);

        $orderIdField = $payload['order_id'] ?? '';
        
        // Extract order ID from "DRSTH-{id}-{timestamp}"
        $parts = explode('-', $orderIdField);
        if (count($parts) < 2) {
            return response()->json(['message' => 'Invalid order id format'], 400);
        }
        $orderId = $parts[1];

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $transactionStatus = $payload['transaction_status'] ?? '';
        $paymentType = $payload['payment_type'] ?? '';
        
        DB::transaction(function () use ($order, $transactionStatus, $paymentType) {
            if (in_array($transactionStatus, ['settlement', 'capture'])) {
                if ($order->status !== 'completed') {
                    $order->update([
                        'status' => 'completed',
                        'payment_type' => $paymentType
                    ]);

                    // Enroll User
                    if ($order->buyable_type === Course::class) {
                        Enrollment::firstOrCreate([
                            'user_id' => $order->user_id,
                            'course_id' => $order->buyable_id,
                        ], [
                            'enrolled_at' => now(),
                        ]);
                    } elseif ($order->buyable_type === Bundle::class) {
                        $bundle = Bundle::find($order->buyable_id);
                        if ($bundle) {
                            // Enroll in bundle itself
                            Enrollment::firstOrCreate([
                                'user_id' => $order->user_id,
                                'bundle_id' => $bundle->id,
                            ], [
                                'enrolled_at' => now(),
                            ]);

                            // Enroll in all courses of this bundle
                            foreach ($bundle->courses as $course) {
                                Enrollment::firstOrCreate([
                                    'user_id' => $order->user_id,
                                    'course_id' => $course->id,
                                ], [
                                    'enrolled_at' => now(),
                                ]);
                            }
                        }
                    }
                }
            } elseif (in_array($transactionStatus, ['pending'])) {
                $order->update(['status' => 'pending']);
            } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $order->update(['status' => 'failed']);
            }
        });

        return response()->json(['message' => 'Notification handled successfully']);
    }

    /**
     * Handle client-side mock payment completion for dev purposes
     */
    public function completeMockPayment(Request $request, Order $order)
    {
        // Only allow this if midtrans credentials are unset (mock mode)
        $serverKey = config('midtrans.server_key');
        if (!empty($serverKey) && !str_contains($serverKey, 'placeholder')) {
            return response()->json(['message' => 'Not allowed in production/configured mode'], 403);
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => 'completed',
                'payment_type' => 'mock_payment'
            ]);

            // Enroll User
            if ($order->buyable_type === Course::class) {
                Enrollment::firstOrCreate([
                    'user_id' => $order->user_id,
                    'course_id' => $order->buyable_id,
                ], [
                    'enrolled_at' => now(),
                ]);
            } elseif ($order->buyable_type === Bundle::class) {
                $bundle = Bundle::find($order->buyable_id);
                if ($bundle) {
                    Enrollment::firstOrCreate([
                        'user_id' => $order->user_id,
                        'bundle_id' => $bundle->id,
                    ], [
                        'enrolled_at' => now(),
                    ]);

                    foreach ($bundle->courses as $course) {
                        Enrollment::firstOrCreate([
                            'user_id' => $order->user_id,
                            'course_id' => $course->id,
                        ], [
                            'enrolled_at' => now(),
                        ]);
                    }
                }
            }
        });

        return response()->json(['message' => 'Mock payment succeeded. User enrolled!']);
    }
}
