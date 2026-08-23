<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view users');
    }

    public function create(User $user): bool
    {
        return $user->can('manage users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('manage users');
    }

    /** Cannot delete your own account. */
    public function delete(User $user, User $model): bool
    {
        return $user->can('manage users') && $user->id !== $model->id;
    }

    /** Cannot impersonate yourself or another administrator. */
    public function impersonate(User $user, User $model): bool
    {
        return $user->can('manage users')
            && $user->id !== $model->id
            && ! $model->hasRole('admin');
    }
}
