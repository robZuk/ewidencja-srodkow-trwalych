<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
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

    /**
     * Step 1 — the target field accepts an incoming transfer. Allowed for an admin
     * or a member of the target field who can operate on assets.
     */
    public function acceptTarget(User $user, TransferRequest $request): bool
    {
        if ($request->type !== TransferType::Transfer || $request->status !== TransferStatus::Pending) {
            return false;
        }

        return $user->hasRole('admin')
            || ($request->target_field_id !== null
                && $user->can('request transfers')
                && $user->belongsToField($request->target_field_id));
    }

    /** Step 2 — the inventory section confirms (also the only step for liquidations). */
    public function acceptInventory(User $user, TransferRequest $request): bool
    {
        return $request->status === TransferStatus::PendingInventory
            && $user->can('decide transfers');
    }

    /** Reject — whoever is responsible for the current step may reject it. */
    public function reject(User $user, TransferRequest $request): bool
    {
        return match ($request->status) {
            TransferStatus::Pending => $this->acceptTarget($user, $request),
            TransferStatus::PendingInventory => $this->acceptInventory($user, $request),
            default => false,
        };
    }

    /** Access to generated druki (ZMU / liquidation PDFs). */
    public function decide(User $user, TransferRequest $request): bool
    {
        return $user->can('decide transfers');
    }
}
