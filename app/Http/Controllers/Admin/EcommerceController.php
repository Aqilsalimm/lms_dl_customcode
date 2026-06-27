<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EcommerceController extends Controller
{
    public function analytics()
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        // 1. Top Performing Courses
        $topCourses = Order::where('status', 'completed')
            ->select('buyable_id', DB::raw('COUNT(*) as sales_count'), DB::raw('SUM(amount) as total_revenue'))
            ->groupBy('buyable_id')
            ->orderByDesc('sales_count')
            ->with(['buyable' => function($query) {
                $query->select('id', 'title', 'slug', 'price');
            }])
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->buyable_id,
                    'title' => $item->buyable ? $item->buyable->title : 'Unknown Course',
                    'slug' => $item->buyable ? $item->buyable->slug : '',
                    'sales_count' => $item->sales_count,
                    'total_revenue' => (float) $item->total_revenue,
                ];
            });

        // 2. Conversion Rate
        $totalCheckouts = Order::count();
        $successfulCheckouts = Order::where('status', 'completed')->count();
        $conversionRate = $totalCheckouts > 0 ? round(($successfulCheckouts / $totalCheckouts) * 100, 1) : 0;

        // 3. Revenue Trend (Daily, Weekly, Monthly)
        // Daily (last 30 days)
        $dailyRaw = Order::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw("DATE(created_at) as date_label"), DB::raw("SUM(amount) as revenue"))
            ->groupBy('date_label')
            ->orderBy('date_label')
            ->get();

        $dailySales = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $match = $dailyRaw->firstWhere('date_label', $date);
            $dailySales[] = [
                'label' => now()->subDays($i)->format('d M'),
                'revenue' => $match ? (float) $match->revenue : 0.0,
            ];
        }

        // Weekly (last 12 weeks)
        $weeklyRaw = Order::where('status', 'completed')
            ->where('created_at', '>=', now()->subWeeks(12))
            ->select(
                DB::raw("YEARWEEK(created_at, 1) as week_key"),
                DB::raw("MIN(created_at) as week_date"),
                DB::raw("SUM(amount) as revenue")
            )
            ->groupBy('week_key')
            ->orderBy('week_key')
            ->get();

        $weeklySales = [];
        for ($i = 11; $i >= 0; $i--) {
            $weekDate = now()->subWeeks($i);
            $weekKey = $weekDate->format('oW');
            $match = $weeklyRaw->first(function($value) use ($weekKey) {
                return $value->week_key == $weekKey;
            });
            $weeklySales[] = [
                'label' => 'Wk ' . $weekDate->format('W'),
                'revenue' => $match ? (float) $match->revenue : 0.0,
            ];
        }

        // Monthly (last 12 months)
        $monthlyRaw = Order::where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_key"),
                DB::raw("SUM(amount) as revenue")
            )
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        $monthlySales = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthKey = $monthDate->format('Y-m');
            $match = $monthlyRaw->firstWhere('month_key', $monthKey);
            $monthlySales[] = [
                'label' => $monthDate->format('M Y'),
                'revenue' => $match ? (float) $match->revenue : 0.0,
            ];
        }

        // 4. Student Growth
        $totalStudents = User::where('role', 'student')->count();
        $newStudentsThisMonth = User::where('role', 'student')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        
        $buyerOrderCounts = Order::where('status', 'completed')
            ->select('user_id', DB::raw('COUNT(*) as order_count'))
            ->groupBy('user_id')
            ->get();

        $totalBuyers = $buyerOrderCounts->count();
        $repeatBuyers = $buyerOrderCounts->where('order_count', '>=', 2)->count();
        $newBuyers = $buyerOrderCounts->where('order_count', 1)->count();
        $repeatBuyerRate = $totalBuyers > 0 ? round(($repeatBuyers / $totalBuyers) * 100, 1) : 0;

        return Inertia::render('Dashboard/Admin/Ecommerce/Analytics', [
            'topCourses' => $topCourses,
            'conversionMetrics' => [
                'total_checkouts' => $totalCheckouts,
                'successful_checkouts' => $successfulCheckouts,
                'conversion_rate' => $conversionRate,
            ],
            'revenueTrends' => [
                'daily' => $dailySales,
                'weekly' => $weeklySales,
                'monthly' => $monthlySales,
            ],
            'studentGrowth' => [
                'total_students' => $totalStudents,
                'new_students_this_month' => $newStudentsThisMonth,
                'total_buyers' => $totalBuyers,
                'repeat_buyers' => $repeatBuyers,
                'new_buyers' => $newBuyers,
                'repeat_buyer_rate' => $repeatBuyerRate,
            ]
        ]);
    }

    public function coupons()
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $coupons = Coupon::with('course')->latest()->get();
        $courses = Course::select('id', 'title')->get();

        return Inertia::render('Dashboard/Admin/Ecommerce/Coupons', [
            'coupons' => $coupons,
            'courses' => $courses
        ]);
    }

    public function storeCoupon(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|string|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'course_id' => 'nullable|exists:courses,id',
            'expires_at' => 'nullable|date',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        Coupon::create($validated);

        return redirect()->back()->with('success', 'Kupon baru berhasil ditambahkan.');
    }

    public function updateCoupon(Request $request, Coupon $coupon)
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code,' . $coupon->id,
            'type' => 'required|string|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'course_id' => 'nullable|exists:courses,id',
            'expires_at' => 'nullable|date',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        $coupon->update($validated);

        return redirect()->back()->with('success', 'Kupon berhasil diperbarui.');
    }

    public function deleteCoupon(Coupon $coupon)
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $coupon->delete();

        return redirect()->back()->with('success', 'Kupon berhasil dihapus.');
    }

    public function settings()
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $keys = [
            'midtrans_client_key',
            'midtrans_server_key',
            'midtrans_sandbox_mode',
            'auto_complete_ecommerce_orders',
            'abandoned_cart_reminder_enabled',
            'abandoned_cart_reminder_delay',
            'abandoned_cart_email_subject',
            'abandoned_cart_email_body',
        ];

        $settings = [];
        $sensitiveKeys = ['midtrans_server_key', 'midtrans_client_key'];

        foreach ($keys as $key) {
            $dbSetting = \App\Models\Setting::where('key', $key)->first();
            
            if ($key === 'abandoned_cart_reminder_enabled') {
                $settings[$key] = $dbSetting ? filter_var($dbSetting->value, FILTER_VALIDATE_BOOLEAN) : false;
            } elseif ($key === 'abandoned_cart_reminder_delay') {
                $settings[$key] = $dbSetting ? (int)$dbSetting->value : 60;
            } elseif ($key === 'midtrans_sandbox_mode') {
                $settings[$key] = $dbSetting ? filter_var($dbSetting->value, FILTER_VALIDATE_BOOLEAN) : true;
            } elseif ($key === 'auto_complete_ecommerce_orders') {
                $settings[$key] = $dbSetting ? filter_var($dbSetting->value, FILTER_VALIDATE_BOOLEAN) : false;
            } elseif ($key === 'abandoned_cart_email_subject') {
                $settings[$key] = $dbSetting ? $dbSetting->value : 'Ayo selesaikan pembelian kelas Anda di Drastha Learning!';
            } elseif ($key === 'abandoned_cart_email_body') {
                $settings[$key] = $dbSetting ? $dbSetting->value : "Halo {student_name},\n\nKami melihat Anda meninggalkan kelas berikut di keranjang belanja Anda:\n{course_names}\n\nJangan biarkan semangat belajar Anda padam! Klik tautan berikut untuk melanjutkan checkout Anda:\n{checkout_link}\n\nSalam Hangat,\nTim Drastha Learning";
            } else {
                $value = $dbSetting ? $dbSetting->value : '';
                if (in_array($key, $sensitiveKeys) && !empty($value)) {
                    $value = $this->maskApiKey($value);
                }
                $settings[$key] = $value;
            }
        }

        return Inertia::render('Dashboard/Admin/Ecommerce/Settings', [
            'settings' => $settings
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'midtrans_client_key' => 'nullable|string',
            'midtrans_server_key' => 'nullable|string',
            'midtrans_sandbox_mode' => 'required|boolean',
            'auto_complete_ecommerce_orders' => 'required|boolean',
            'abandoned_cart_reminder_enabled' => 'required|boolean',
            'abandoned_cart_reminder_delay' => 'required|integer|min:5',
            'abandoned_cart_email_subject' => 'required|string|max:255',
            'abandoned_cart_email_body' => 'required|string',
        ]);

        foreach ($validated as $key => $value) {
            if ($this->isMaskedValue($value)) {
                continue;
            }
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : $value]
            );
        }

        return redirect()->back()->with('success', 'Pengaturan e-Commerce berhasil diperbarui.');
    }

    /**
     * Mask API keys to protect sensitive credentials from exposing on the client side
     */
    private function maskApiKey(?string $key): string
    {
        if (empty($key)) {
            return '';
        }
        if (strlen($key) <= 8) {
            return '********';
        }
        return substr($key, 0, 4) . str_repeat('*', strlen($key) - 8) . substr($key, -4);
    }

    /**
     * Check if a setting value is a masked placeholder
     */
    private function isMaskedValue($value): bool
    {
        return is_string($value) && str_contains($value, '*');
    }
}
