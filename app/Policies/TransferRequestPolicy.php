<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TransferRequest;
use App\Models\User;

class TransferRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view assets');
    }

    public function view(User $user, TransferRequest $request): bool
    {
        return $user->can('view assets');
    }

    /** Initiate a transfer or liquidation request. */
    public function create(User $user): bool
    {
        return $user->can('request transfers');
    }

    /** Accept/reject on behalf of the target field or the inventory section. */
    public function decide(User $user, TransferRequest $request): bool
    {
        return $user->can('decide transfers');
    }
}
