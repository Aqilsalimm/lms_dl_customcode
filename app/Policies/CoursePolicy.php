<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function view(User $user, Course $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->instructor_id)) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }

    public function create(User $user)
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function update(User $user, Course $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->instructor_id)) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }

    public function delete(User $user, Course $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->instructor_id)) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }

    public function restore(User $user, Course $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->instructor_id)) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }

    public function forceDelete(User $user, Course $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->instructor_id)) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }

    public function report(User $user, Course $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor()) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }

    public function gift(User $user, Course $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor()) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }

    public function joinLiveSession(User $user, Course $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && $model->instructor_id === $user->id) return true;

        // Ensure user is enrolled
        $isEnrolled = \App\Models\Enrollment::where('user_id', $user->id)
            ->where('course_id', $model->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if (!$isEnrolled) return false;

        // Ensure pre-test is completed if it exists
        $preTest = \App\Models\WorkshopAssessment::where('course_id', $model->id)
            ->where('type', 'pre_test')
            ->where('is_published', true)
            ->first();

        if ($preTest) {
            $passedPreTest = \App\Models\WorkshopAssessmentAttempt::where('assessment_id', $preTest->id)
                ->where('user_id', $user->id)
                ->where('is_passed', true)
                ->exists();
            if (!$passedPreTest) return false;
        }

        return true;
    }
}