<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopAssessmentAttempt extends Model
{
    protected $fillable = [
        'assessment_id',
        'user_id',
        'status',
        'total_score',
        'is_passed',
        'attempt_number',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'total_score' => 'float',
        'is_passed' => 'boolean',
        'attempt_number' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the assessment.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(WorkshopAssessment::class, 'assessment_id');
    }

    /**
     * Get the user who took this attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all answers submitted for this attempt.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(WorkshopAssessmentUserAnswer::class, 'attempt_id');
    }

    public function userAnswers(): HasMany
    {
        return $this->answers();
    }
}
