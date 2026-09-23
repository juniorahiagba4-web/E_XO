<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Deliberately defines only `delete`/`deleteAny` — Filament falls back to its
 * default (allowed) behaviour for any ability a policy doesn't declare, so
 * leaving every other ability undefined here keeps view/create/update open
 * to admin and manager alike, restricting only deletion to admins.
 */
trait RestrictsDeletionToAdmin
{
    public function delete(User $user, Model $model): bool
    {
        return $user->hasRole('admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
