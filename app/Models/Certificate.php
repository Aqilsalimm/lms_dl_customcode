<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Certificate extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'type',
        'module_ids',
        'description',
        'is_active',
    ];

    protected $casts = [
        'module_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function userCertificates(): HasMany
    {
        return $this->hasMany(UserCertificate::class);
    }

    /**
     * Check if this certificate is unlocked for a given user
     */
    public function getUnlockStatusForUser(?User $user): array
    {
        if (!$user) {
            return [
                'unlocked' => false,
                'progress_count' => 0,
                'total_required' => count($this->module_ids ?? []),
                'percentage' => 0,
                'reason' => 'User belum terautentikasi.',
            ];
        }

        // Admins and course instructor can always unlock/preview
        if ($user->isAdmin() || $user->id === $this->course->instructor_id) {
            return [
                'unlocked' => true,
                'progress_count' => count($this->module_ids ?? []),
                'total_required' => count($this->module_ids ?? []),
                'percentage' => 100,
                'reason' => 'Akses Khusus Admin/Instruktur.',
            ];
        }

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $this->course_id)
            ->first();

        if (!$enrollment) {
            return [
                'unlocked' => false,
                'progress_count' => 0,
                'total_required' => count($this->module_ids ?? []),
                'percentage' => 0,
                'reason' => 'Peserta belum terdaftar pada kelas ini.',
            ];
        }

        // If completion type and enrollment marked completed
        if ($this->type === 'course_completion' && $enrollment->completed_at !== null) {
            return [
                'unlocked' => true,
                'progress_count' => 1,
                'total_required' => 1,
                'percentage' => 100,
                'reason' => 'Selamat! Kelas telah diselesaikan secara penuh.',
            ];
        }

        $requiredModuleIds = $this->module_ids ?: [];
        if (empty($requiredModuleIds)) {
            // Default: require all modules in course
            $requiredModuleIds = Module::where('course_id', $this->course_id)->pluck('id')->toArray();
        }

        $totalRequired = count($requiredModuleIds);
        if ($totalRequired === 0) {
            return [
                'unlocked' => true,
                'progress_count' => 0,
                'total_required' => 0,
                'percentage' => 100,
                'reason' => 'Sertifikat tidak membutuhkan modul prasyarat.',
            ];
        }

        $completedCount = 0;
        $completedLessons = $enrollment->completed_lessons ?? [];
        $completedQuizzes = $enrollment->completed_quizzes ?? [];

        foreach ($requiredModuleIds as $modId) {
            $module = Module::with(['lessons', 'quizzes', 'assessments'])->find($modId);
            if (!$module) continue;

            $lessonsCount = $module->lessons->count();
            $quizzesCount = $module->quizzes->count();

            $modLessonsCompleted = 0;
            foreach ($module->lessons as $les) {
                if (in_array($les->id, $completedLessons)) {
                    $modLessonsCompleted++;
                }
            }

            $modQuizzesCompleted = 0;
            foreach ($module->quizzes as $q) {
                if (in_array($q->id, $completedQuizzes)) {
                    $modQuizzesCompleted++;
                }
            }

            // Check post-test if assessment enabled
            $assessmentPassed = true;
            if ($module->enable_assessment) {
                $postTest = $module->assessments()->where('type', 'post_test')->first();
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

            $isModLessonsDone = ($lessonsCount === 0 || $modLessonsCompleted >= $lessonsCount);
            $isModQuizzesDone = ($quizzesCount === 0 || $modQuizzesCompleted >= $quizzesCount);

            if ($isModLessonsDone && $isModQuizzesDone && $assessmentPassed) {
                $completedCount++;
            }
        }

        $percentage = $totalRequired > 0 ? round(($completedCount / $totalRequired) * 100) : 100;
        $unlocked = ($completedCount >= $totalRequired);

        $reason = $unlocked 
            ? 'Seluruh syarat modul untuk sertifikat ini telah terpenuhi.'
            : "Selesaikan " . ($totalRequired - $completedCount) . " sesi lagi untuk membuka sertifikat ini (Progres: {$completedCount}/{$totalRequired} Sesi).";

        return [
            'unlocked' => $unlocked,
            'progress_count' => $completedCount,
            'total_required' => $totalRequired,
            'percentage' => $percentage,
            'reason' => $reason,
        ];
    }
}
