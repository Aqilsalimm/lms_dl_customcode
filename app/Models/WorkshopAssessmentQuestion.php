<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopAssessmentQuestion extends Model
{
    protected $fillable = [
        'assessment_id',
        'question_text',
        'image_url',
        'options',
        'correct_answer',
        'points',
        'order_number',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'integer',
        'order_number' => 'integer',
    ];

    /**
     * Get the assessment that owns this question.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(WorkshopAssessment::class, 'assessment_id');
    }

    /**
     * Get all answers submitted by students for this question.
     */
    public function userAnswers(): HasMany
    {
        return $this->hasMany(WorkshopAssessmentUserAnswer::class, 'question_id');
    }
}
