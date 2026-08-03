<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function view(User $user, Certificate $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->course->instructor_id)) {
            return $model->course->instructor_id === $user->id;
        }
        return false;
    }

    public function create(User $user)
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function update(User $user, Certificate $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->course->instructor_id)) {
            return $model->course->instructor_id === $user->id;
        }
        return false;
    }

    public function delete(User $user, Certificate $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->course->instructor_id)) {
            return $model->course->instructor_id === $user->id;
        }
        return false;
    }
}
