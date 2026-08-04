<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    public const PURPOSE_USER_DELETE = 'user_delete';

    protected $fillable = ['user_id', 'email', 'otp_code', 'purpose', 'expires_at', 'used'];

    protected $hidden = ['otp_code'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
