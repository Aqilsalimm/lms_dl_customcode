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
        'has_session_certificate',
        'certificate_bg_path',
        'text_name_y_position',
        'text_title_y_position',
    ];

    protected $casts = [
        'enable_assessment' => 'boolean',
        'has_session_certificate' => 'boolean',
        'text_name_y_position' => 'integer',
        'text_title_y_position' => 'integer',
    ];

    protected static function booted()
    {
        static::saved(function ($module) {
            $module->invalidateCourseCache();
        });

        static::deleted(function ($module) {
            $module->invalidateCourseCache();
        });
    }

    private function invalidateCourseCache()
    {
        \Illuminate\Support\Facades\Cache::flush();
    }

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

    /**
     * Check if this module is fully completed by a given user
     */
    public function isCompletedBy(?User $user): bool
    {
        if (!$user) return false;

        // Admins and instructors always pass
        if ($user->isAdmin() || $user->id === $this->course->instructor_id) {
            return true;
        }

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $this->course_id)
            ->first();

        if (!$enrollment) return false;

        $completedLessons = $enrollment->completed_lessons ?? [];
        $completedQuizzes = $enrollment->completed_quizzes ?? [];

        $lessonsCount = $this->lessons()->count();
        $quizzesCount = $this->quizzes()->count();

        $modLessonsCompleted = $this->lessons()->whereIn('id', $completedLessons)->count();
        $modQuizzesCompleted = $this->quizzes()->whereIn('id', $completedQuizzes)->count();

        $lessonsDone = ($lessonsCount === 0 || $modLessonsCompleted >= $lessonsCount);
        $quizzesDone = ($quizzesCount === 0 || $modQuizzesCompleted >= $quizzesCount);

        // Check post-test if assessment enabled
        $assessmentPassed = true;
        if ($this->enable_assessment) {
            $postTest = $this->assessments()->where('type', 'post_test')->first();
            if ($postTest) {
                $passedAttempt = WorkshopAssessmentAttempt::where('user_id', $user->id)
                    ->where('assessment_id', $postTest->id)
                    ->where('status', 'completed')
                    ->where('is_passed', true)
                    ->exists();
                if (!$passedAttempt) {
                    $assessmentPassed = false;
                }
            }
        }

        return $lessonsDone && $quizzesDone && $assessmentPassed;
    }
}
