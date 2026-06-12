<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Bundle;
use App\Models\Order;
use App\Models\Enrollment;
use App\Models\Coupon;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    /**
     * Display the shopping cart page.
     */
    public function index()
    {
        $cartIds = session('cart', []);
        $cartItems = [];

        if (!empty($cartIds)) {
            $cartItems = Course::whereIn('id', $cartIds)
                ->with(['category'])
                ->get();
        }

        return Inertia::render('Cart/Index', [
            'cartItems' => $cartItems,
        ]);
    }

    /**
     * Display the checkout page.
     */
    public function checkoutPage()
    {
        $cartIds = session('cart', []);
        $cartItems = [];

        if (!empty($cartIds)) {
            $cartItems = Course::whereIn('id', $cartIds)
                ->with(['category'])
                ->get();
        }

        if (empty($cartItems)) {
            return redirect()->route('courses.index')->with('warning', 'Pilihlah kelas terlebih dahulu sebelum checkout.');
        }

        $clientKey = \App\Models\Setting::where('key', 'midtrans_client_key')->value('value') ?: config('midtrans.client_key', 'SB-Mid-client-placeholder');
        $sandboxMode = \App\Models\Setting::where('key', 'midtrans_sandbox_mode')->value('value');
        $isSandbox = $sandboxMode === null ? !config('midtrans.is_production', false) : filter_var($sandboxMode, FILTER_VALIDATE_BOOLEAN);

        return Inertia::render('Checkout/Index', [
            'cartItems' => $cartItems,
            'midtransClientKey' => $clientKey,
            'midtransSandboxMode' => $isSandbox
        ]);
    }

    /**
     * Add a course to the shopping cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        $cart = session('cart', []);

        // Avoid duplicates
        if (!in_array($request->course_id, $cart)) {
            $cart[] = $request->course_id;
            session(['cart' => $cart]);
        }

        return redirect()->back()->with('success', 'Kelas berhasil ditambahkan ke keranjang belanja.');
    }

    /**
     * Remove a course from the shopping cart.
     */
    public function remove(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer',
        ]);

        $cart = session('cart', []);

        if (($key = array_search($request->course_id, $cart)) !== false) {
            unset($cart[$key]);
            session(['cart' => array_values($cart)]);
        }

        return redirect()->back()->with('success', 'Kelas berhasil dihapus dari keranjang belanja.');
    }

    /**
     * Clear all courses from the shopping cart.
     */
    public function clear()
    {
        session()->forget('cart');
        return redirect()->back()->with('success', 'Keranjang belanja dikosongkan.');
    }

    /**
     * Checkout all items in the cart by compiling them into a dynamic Bundle Order.
     */
    public function checkout(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Silakan login terlebih dahulu.'], 401);
        }

        $request->validate([
            'coupon_code' => 'nullable|string',
        ]);

        $cartIds = session('cart', []);
        if (empty($cartIds)) {
            return response()->json(['message' => 'Keranjang belanja Anda kosong.'], 400);
        }

        $courses = Course::whereIn('id', $cartIds)->get();
        if ($courses->isEmpty()) {
            return response()->json(['message' => 'Kelas tidak ditemukan.'], 404);
        }

        $totalPrice = $courses->sum('price');

        $couponId = null;
        $discountAmount = 0.00;
        $finalAmount = $totalPrice;

        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();
            if ($coupon && $coupon->is_active) {
                if ($coupon->course_id !== null) {
                    $targetCourse = $courses->firstWhere('id', $coupon->course_id);
                    if ($targetCourse && $coupon->isValidForCourse($targetCourse->id)) {
                        $couponId = $coupon->id;
                        $discountAmount = $coupon->calculateDiscount($targetCourse->price);
                        $finalAmount = max(0.00, $totalPrice - $discountAmount);
                    }
                } else {
                    if ($coupon->isValidForCourse(null)) {
                        $couponId = $coupon->id;
                        $discountAmount = $coupon->calculateDiscount($totalPrice);
                        $finalAmount = max(0.00, $totalPrice - $discountAmount);
                    }
                }
            }
        }

        // Compile items into a dynamic Bundle
        $bundle = Bundle::create([
            'title' => 'Keranjang Belanja - ' . $user->name . ' - ' . now()->format('d M Y H:i'),
            'description' => 'Pembelian gabungan dari Keranjang Belanja.',
            'price' => $finalAmount,
            'status' => 'published',
        ]);

        // Attach course IDs
        $bundle->courses()->attach($cartIds);

        $autoCompleteOrders = filter_var(
            \App\Models\Setting::where('key', 'auto_complete_ecommerce_orders')->value('value'),
            FILTER_VALIDATE_BOOLEAN
        );

        $isFree = $finalAmount <= 0;
        $orderStatus = ($autoCompleteOrders || $isFree) ? 'completed' : 'pending';
        $paymentType = ($autoCompleteOrders || $isFree) ? 'auto_complete' : null;

        // Create Order
        $order = Order::create([
            'user_id' => $user->id,
            'buyable_type' => Bundle::class,
            'buyable_id' => $bundle->id,
            'amount' => $finalAmount,
            'status' => $orderStatus,
            'payment_type' => $paymentType,
            'coupon_id' => $couponId,
            'discount_amount' => $discountAmount,
        ]);

        if ($order->status === 'completed') {
            if ($couponId) {
                Coupon::find($couponId)->increment('uses');
            }

            // Enroll in bundle
            Enrollment::firstOrCreate([
                'user_id' => $user->id,
                'bundle_id' => $bundle->id,
            ], [
                'enrolled_at' => now(),
            ]);

            // Enroll in all courses of this bundle
            foreach ($courses as $course) {
                Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ], [
                    'enrolled_at' => now(),
                ]);
            }

            // Clear the cart
            session()->forget('cart');

            return response()->json([
                'completed' => true,
                'order_id' => $order->id,
                'snap_token' => 'MOCK-SNAP-TOKEN-AUTOCOMPLETE'
            ]);
        }

        // Generate Snap Token
        $midtransService = app(MidtransService::class);
        $snapToken = $midtransService->createSnapToken($order);
        $order->update(['snap_token' => $snapToken]);

        // Clear the cart on successful checkout creation
        session()->forget('cart');

        return response()->json([
            'snap_token' => $snapToken,
            'order_id' => $order->id,
        ]);
    }

    /**
     * Resume an abandoned checkout by restoring cart items and redirecting.
     */
    public function resumeCheckout(Order $order)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login', ['redirect' => route('checkout.resume', $order->id)]);
        }

        if ($order->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this order.');
        }

        if ($order->status === 'completed') {
            return redirect()->route('dashboard')->with('success', 'Transaksi ini telah selesai dibayar.');
        }

        // Restore cart items
        $cartIds = [];
        if ($order->buyable_type === Course::class) {
            $cartIds[] = $order->buyable_id;
        } elseif ($order->buyable_type === Bundle::class) {
            $bundle = Bundle::find($order->buyable_id);
            if ($bundle) {
                $cartIds = $bundle->courses()->pluck('courses.id')->toArray();
            }
        }

        if (!empty($cartIds)) {
            session(['cart' => $cartIds]);
        }

        return redirect()->route('cart.checkout-page')->with('info', 'Checkout Anda berhasil dipulihkan.');
    }
}
