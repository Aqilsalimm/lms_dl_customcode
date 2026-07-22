<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'course_id', 
        'title', 
        'sort_order',
        'meeting_url',
        'start_date',
        'end_date',
        'recording_url',
        'material_file_path',
        'enable_assessment',
    ];

    protected $casts = [
        'enable_assessment' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class)->orderBy('sort_order');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(WorkshopAssessment::class);
    }
}
