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
        'payment_type'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buyable(): MorphTo
    {
        return $this->morphTo();
    }
}
