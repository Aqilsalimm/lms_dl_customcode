<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'buyable_type',
        'buyable_id',
        'amount',
        'status',
        'transaction_id',
        'snap_token',
        'payment_type',
        'coupon_id',
        'discount_amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saved(function ($order) {
            // Check if order became 'completed'
            if ($order->status === 'completed' && ($order->wasRecentlyCreated || $order->wasChanged('status'))) {
                try {
                    \App\Services\InvoiceService::generateAndSend($order);
                } catch (\Exception $e) {
                    logger()->error("Failed to generate/send invoice for order #{$order->id}: " . $e->getMessage());
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function buyable(): MorphTo
    {
        return $this->morphTo();
    }
}
