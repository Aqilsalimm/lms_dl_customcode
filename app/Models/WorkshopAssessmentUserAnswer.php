<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopAssessmentUserAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_answer',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    /**
     * Get the attempt session.
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(WorkshopAssessmentAttempt::class, 'attempt_id');
    }

    /**
     * Get the question answered.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(WorkshopAssessmentQuestion::class, 'question_id');
    }
}
