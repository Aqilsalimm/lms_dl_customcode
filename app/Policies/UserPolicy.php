<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
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

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model)
    {
        if ($user->isAdmin()) return true;
        if ($user->isInstructor() && isset($model->instructor_id)) {
            return $model->instructor_id === $user->id;
        }
        return false;
    }

    public function delete(User $user, User $model): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny('Hanya administrator yang dapat menghapus pengguna.');
        }

        if ($user->is($model)) {
            return Response::deny('Administrator tidak dapat menghapus akunnya sendiri.');
        }

        return $model->isAdmin()
            ? Response::deny('Akun administrator tidak dapat dihapus.')
            : Response::allow();
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $model->isAdmin();
    }
}
