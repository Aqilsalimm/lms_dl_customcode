<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'member_type',
        'member_number',
        'division',
        'position',
        'starts_at',
        'ends_at',
        'profile_completed_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'profile_completed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'organization_membership_id');
    }

    public function instructedCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_organization_membership_id');
    }
}
