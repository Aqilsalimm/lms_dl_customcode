<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model)
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

    public function update(User $user, User $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->instructor_id)) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }

    public function delete(User $user, User $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->instructor_id)) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }
}
