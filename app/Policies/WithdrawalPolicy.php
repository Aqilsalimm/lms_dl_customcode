<?php

namespace App\Policies;

use App\Models\Withdrawal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WithdrawalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function view(User $user, Withdrawal $model)
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

    public function update(User $user, Withdrawal $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->instructor_id)) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }

    public function delete(User $user, Withdrawal $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->instructor_id)) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }
}