<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Course $course): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Course $course): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Course $course): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Course $course): bool
    {
        return false;
    }

    /**
     * Tentukan apakah user boleh masuk ke ruang Live Class (Zoom/Meet).
     */
    public function joinLiveSession(User $user, Course $course)
    {
        // Admin always has access
        if ($user->isAdmin()) {
            return true;
        }

        // Course instructor always has access
        if ($course->instructor_id === $user->id) {
            return true;
        }

        // 1. Cek dasar: Apakah kelas ini memang tipe live/workshop?
        $courseType = $course->course_type ?? $course->type ?? null;
        if ($courseType !== 'live_class' && $courseType !== 'live') {
            return false;
        }

        // 2. Cek pendaftaran: Apakah user memang terdaftar/membeli kelas ini?
        // (Asumsi Anda punya tabel atau relasi enrollments)
        if (! $user->enrollments()->where('course_id', $course->id)->exists()) {
            return false;
        }

        // 3. Cek keberadaan Pre-test: Apakah kelas ini punya Pre-test?
        $preTest = $course->assessments()->where('type', 'pre_test')->first();
        
        // Jika tidak ada Pre-test yang diatur, langsung loloskan
        if (!$preTest) {
            return true;
        }

        // 4. Cek Kelulusan Pre-test: Gunakan fungsi helper yang kita buat di Model User
        return $user->hasPassedAssessment($course->id, 'pre_test');
    }
}
