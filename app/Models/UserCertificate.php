<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCertificate extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_id',
        'source_enrollment_id',
        'certificate_code',
        'claimed_at',
        'identity_snapshot',
        'snapshot_version',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'identity_snapshot' => 'array',
        'snapshot_version' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function sourceEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'source_enrollment_id');
    }
}
