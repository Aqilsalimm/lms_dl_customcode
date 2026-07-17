<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopAssessment extends Model
{
    protected $fillable = [
        'course_id',
        'type',
        'title',
        'description',
        'duration_minutes',
        'passing_score',
        'is_published',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'is_published' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the course (workshop/live class) that owns this assessment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get all questions for this assessment.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(WorkshopAssessmentQuestion::class, 'assessment_id')->orderBy('order_number');
    }

    /**
     * Get all attempts made by students for this assessment.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(WorkshopAssessmentAttempt::class, 'assessment_id');
    }
}
