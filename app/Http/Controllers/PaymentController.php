<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Bundle;
use App\Models\Coupon;
use App\Models\User;
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
            'coupon_code' => 'nullable|string',
        ]);

        $user = auth()->user();

        // Get buyable item
        if ($request->buyable_type === 'course') {
            $item = Course::findOrFail($request->buyable_id);
            $buyableClass = Course::class;

            if ($item->course_type === 'live_class' && $item->max_participants) {
                $currentParticipants = Enrollment::where('course_id', $item->id)->count();
                if ($currentParticipants >= $item->max_participants) {
                    return back()->with('error', 'Mohon maaf, kuota peserta untuk Live Class ini sudah penuh.');
                }
            }
        } else {
            $item = Bundle::findOrFail($request->buyable_id);
            $buyableClass = Bundle::class;

            foreach($item->courses as $c) {
                if ($c->course_type === 'live_class' && $c->max_participants) {
                    $currentParticipants = Enrollment::where('course_id', $c->id)->count();
                    if ($currentParticipants >= $c->max_participants) {
                        return back()->with('error', 'Mohon maaf, salah satu kelas dalam bundle ini ('.$c->title.') sudah penuh kuotanya.');
                    }
                }
            }
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

        $couponId = null;
        $discountAmount = 0.00;
        $finalAmount = $item->price;

        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();
            if ($coupon) {
                if ($coupon->isValidForCourse($request->buyable_type === 'course' ? $item->id : null)) {
                    $couponId = $coupon->id;
                    $discountAmount = $coupon->calculateDiscount($item->price);
                    $finalAmount = max(0.00, $item->price - $discountAmount);
                }
            }
        }

        $autoCompleteOrders = filter_var(
            \App\Models\Setting::getValue('auto_complete_ecommerce_orders'),
            FILTER_VALIDATE_BOOLEAN
        );

        $isFree = $finalAmount <= 0;
        $isFree = $finalAmount <= 0;
        $orderStatus = 'pending';
        $paymentType = null;

        // Create Order
        $order = Order::create([
            'user_id' => $user->id,
            'buyable_type' => $buyableClass,
            'buyable_id' => $item->id,
            'amount' => $finalAmount,
            'status' => $orderStatus,
            'payment_type' => $paymentType,
            'coupon_id' => $couponId,
            'discount_amount' => $discountAmount,
        ]);

        if ($autoCompleteOrders || $isFree) {
            app(\App\Services\Payment\OrderSettlementService::class)->settle($order, 'auto_complete');

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
        $orderIdField = $payload['order_id'] ?? '';
        
        // Handle Midtrans test notification ping
        if (str_contains($orderIdField, 'test') || str_contains($orderIdField, 'payment_notif_test')) {
            logger()->info('Midtrans Test Notification Ping successful');
            return response()->json(['message' => 'Test notification received successfully'], 200);
        }

        $isConfigured = \App\Services\Payment\MidtransConfig::isConfigured();
        $isLocalEnv = app()->environment('local', 'testing');

        if (!$isConfigured && !$isLocalEnv) {
            logger()->warning('Midtrans Webhook: Payment gateway not configured. Request rejected.');
            return response()->json(['message' => 'Payment gateway not configured'], 503);
        }

        if ($isConfigured) {
            $statusCode = $payload['status_code'] ?? '';
            $grossAmount = $payload['gross_amount'] ?? '';
            $signatureKeyReceived = $payload['signature_key'] ?? '';
            $serverKey = \App\Services\Payment\MidtransConfig::serverKey();

            // Format of signature key: SHA512(order_id + status_code + gross_amount + server_key)
            $input = $orderIdField . $statusCode . $grossAmount . $serverKey;
            $signatureKeyCalculated = hash('sha512', $input);

            if (!hash_equals($signatureKeyCalculated, $signatureKeyReceived)) {
                logger()->warning('Midtrans Webhook: Signature Key Verification Failed.', [
                    'order_id' => $orderIdField
                ]);
                return response()->json(['message' => 'Invalid signature key'], 403);
            }
        } else {
            logger()->info('Midtrans Webhook: Bypassing signature verification in development (placeholder server key).');
        }

        $transactionStatus = $payload['transaction_status'] ?? '';
        $paymentType = $payload['payment_type'] ?? '';
        $fraudStatus = $payload['fraud_status'] ?? null;

        logger()->info('Midtrans Webhook Received', array_filter([
            'order_id' => $orderIdField,
            'transaction_status' => $transactionStatus,
            'payment_type' => $paymentType,
            'fraud_status' => $fraudStatus,
        ]));

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
        
        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            app(\App\Services\Payment\OrderSettlementService::class)->settle($order, $paymentType);
        } elseif (in_array($transactionStatus, ['pending'])) {
            $order->update(['status' => 'pending']);
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $order->update(['status' => 'failed']);
        }

        return response()->json(['message' => 'Notification handled successfully']);
    }

    /**
     * Handle client-side mock payment completion for dev purposes
     */
    public function completeMockPayment(Request $request, Order $order)
    {
        abort_unless(app()->environment('local', 'testing'), 403, 'Not allowed in production mode');
        abort_unless($order->user_id === auth()->id(), 403, 'Unauthorized action.');

        app(\App\Services\Payment\OrderSettlementService::class)->settle($order, 'mock_payment');

        return response()->json(['message' => 'Mock payment succeeded. User enrolled!']);
    }

    /**
     * Validate Coupon endpoint
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'items' => 'required|array',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();
        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'Kode kupon tidak valid.'], 422);
        }

        if (!$coupon->is_active) {
            return response()->json(['valid' => false, 'message' => 'Kupon ini sudah tidak aktif.'], 422);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return response()->json(['valid' => false, 'message' => 'Kupon ini sudah kedaluwarsa.'], 422);
        }

        if ($coupon->max_uses !== null && $coupon->uses >= $coupon->max_uses) {
            return response()->json(['valid' => false, 'message' => 'Kupon ini sudah mencapai batas kuota.'], 422);
        }

        // Check course specific constraint
        if ($coupon->course_id !== null) {
            $hasCourse = false;
            $coursePrice = 0;
            foreach ($request->items as $item) {
                if ($item['type'] === 'course' && $item['id'] == $coupon->course_id) {
                    $hasCourse = true;
                    $courseModel = Course::find($item['id']);
                    if ($courseModel) {
                        $coursePrice = $courseModel->price;
                    }
                    break;
                }
            }

            if (!$hasCourse) {
                $courseName = $coupon->course ? $coupon->course->title : 'kelas tertentu';
                return response()->json(['valid' => false, 'message' => "Kupon ini hanya berlaku untuk kelas: {$courseName}."], 422);
            }

            $discount = $coupon->calculateDiscount($coursePrice);
        } else {
            $totalPrice = 0;
            foreach ($request->items as $item) {
                if ($item['type'] === 'course') {
                    $m = Course::find($item['id']);
                    if ($m) $totalPrice += $m->price;
                } else {
                    $m = Bundle::find($item['id']);
                    if ($m) $totalPrice += $m->price;
                }
            }
            $discount = $coupon->calculateDiscount($totalPrice);
        }

        return response()->json([
            'valid' => true,
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'discount_amount' => (float) $discount,
        ]);
    }

    /**
     * Download Invoice PDF
     */
    public function downloadInvoice(Order $order)
    {
        $user = auth()->user();
        if ($order->user_id !== $user->id && $user->role !== 'admin') {
            abort(403, 'Anda tidak memiliki akses ke invoice ini.');
        }

        if ($order->status !== 'completed') {
            abort(400, 'Invoice belum tersedia karena transaksi belum selesai.');
        }

        $pdfContent = \App\Services\InvoiceService::generatePdf($order);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Invoice-DRSTH-' . $order->id . '.pdf"',
        ]);
    }



    /**
     * Cancel a pending order
     */
    public function cancel(Order $order)
    {
        $user = auth()->user();
        if ($order->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this order.');
        }

        if ($order->status !== 'pending') {
            return back()->with('error', 'Hanya pesanan pending yang dapat dibatalkan.');
        }

        $order->update(['status' => 'failed']);

        return back()->with('success', 'Pesanan berhasil dibatalkan.');
    }
}
