<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only admins manage panel accounts — managers have no reason to see,
     * create, or edit other users' access.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('admin') && ! $user->is($model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
