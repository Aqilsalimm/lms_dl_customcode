<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Bundle;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class OrderSettlementService
{
    /**
     * Settle the order idempotently
     *
     * @param Order $order
     * @param string $paymentType
     * @return void
     */
    public function settle(Order $order, string $paymentType = ''): void
    {
        DB::transaction(function () use ($order, $paymentType) {
            $order->lockForUpdate();

            if ($order->status !== 'pending') {
                return;
            }

            $order->update([
                'status' => 'completed',
                'payment_type' => $paymentType ?: $order->payment_type
            ]);

            if ($order->coupon_id) {
                Coupon::find($order->coupon_id)->increment('uses');
            }

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

            // Allocate Revenue Share to Instructor
            $this->allocateRevenueShare($order);
        });
    }

    /**
     * Allocate Revenue Share to Instructor
     *
     * @param Order $order
     * @return void
     */
    private function allocateRevenueShare(Order $order): void
    {
        if ($order->amount <= 0) {
            return;
        }

        $buyable = $order->buyable_type::find($order->buyable_id);
        if ($buyable && isset($buyable->instructor_id)) {
            $instructor = User::find($buyable->instructor_id);
            if ($instructor) {
                $percentage = (float) (Setting::getValue('sharing_percentage_instructor', 70));
                $shareAmount = ($order->amount * $percentage) / 100;

                if ($shareAmount > 0) {
                    $instructor->increment('balance', $shareAmount);
                }
            }
        }
    }
}
