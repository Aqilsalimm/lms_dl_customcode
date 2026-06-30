<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'start_date',
        'next_billing_date',
        'is_gifted',
        'gifted_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_billing_date' => 'date',
        'is_gifted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
