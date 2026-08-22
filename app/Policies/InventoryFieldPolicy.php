<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InventoryField;
use App\Models\User;

class InventoryFieldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage assets');
    }

    public function create(User $user): bool
    {
        return $user->can('manage assets');
    }

    public function update(User $user, InventoryField $field): bool
    {
        return $user->can('manage assets');
    }

    /** A field cannot be deleted while assets are still assigned to it. */
    public function delete(User $user, InventoryField $field): bool
    {
        return $user->can('manage assets') && $field->assets()->doesntExist();
    }
}
