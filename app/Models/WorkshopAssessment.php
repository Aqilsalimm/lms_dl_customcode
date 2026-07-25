<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopAssessment extends Model
{
    protected $fillable = [
        'course_id',
        'module_id',
        'type',
        'title',
        'description',
        'use_global_settings',
        'duration_minutes',
        'passing_score',
        'max_attempts',
        'is_published',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'use_global_settings' => 'boolean',
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'max_attempts' => 'integer',
        'is_published' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get effective duration in minutes (resolves global setting if use_global_settings is true).
     */
    public function getEffectiveDurationMinutesAttribute(): int
    {
        if ($this->use_global_settings) {
            return (int) (\App\Models\Setting::where('key', 'test_builder_default_duration')->value('value') ?: 30);
        }
        return (int) ($this->duration_minutes ?: 30);
    }

    /**
     * Get effective passing score percentage (resolves global setting if use_global_settings is true).
     */
    public function getEffectivePassingScoreAttribute(): int
    {
        if ($this->use_global_settings) {
            $settingKey = ($this->type === 'pre_test') ? 'test_builder_pre_passing_score' : 'test_builder_post_passing_score';
            return (int) (\App\Models\Setting::where('key', $settingKey)->value('value') ?: 70);
        }
        return (int) ($this->passing_score ?: 70);
    }

    /**
     * Get effective maximum attempts (resolves global setting if use_global_settings is true).
     */
    public function getEffectiveMaxAttemptsAttribute(): int
    {
        if ($this->use_global_settings) {
            return (int) (\App\Models\Setting::where('key', 'test_builder_default_max_attempts')->value('value') ?: 3);
        }
        return (int) ($this->max_attempts !== null ? $this->max_attempts : 3);
    }

    /**
     * Get the course (workshop/live class) that owns this assessment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the module (session) that owns this assessment.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
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
