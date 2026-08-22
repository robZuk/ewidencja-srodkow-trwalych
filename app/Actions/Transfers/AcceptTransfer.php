<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Enums\AssetStatus;
use App\Enums\TransferStatus;
use App\Models\TransferRequest;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Advances a transfer request through its two approval steps:
 *   Pending           → PendingInventory   (target field accepts)
 *   PendingInventory  → Completed          (inventory section confirms & moves the asset)
 */
class AcceptTransfer
{
    public function handle(TransferRequest $request, User $decider): TransferRequest
    {
        return match ($request->status) {
            TransferStatus::Pending => $this->acceptByTarget($request, $decider),
            TransferStatus::PendingInventory => $this->finalize($request, $decider),
            default => throw new DomainException('Nie można zaakceptować przekazania w tym stanie.'),
        };
    }

    private function acceptByTarget(TransferRequest $request, User $decider): TransferRequest
    {
        $request->update([
            'status' => TransferStatus::PendingInventory,
            'target_accepted_by' => $decider->id,
        ]);

        return $request;
    }

    private function finalize(TransferRequest $request, User $decider): TransferRequest
    {
        DB::transaction(function () use ($request, $decider): void {
            $request->asset?->update([
                'inventory_field_id' => $request->target_field_id,
                'status' => AssetStatus::Available,
            ]);

            $request->update([
                'status' => TransferStatus::Completed,
                'inventory_accepted_by' => $decider->id,
                'resolved_at' => now(),
            ]);
        });

        return $request->refresh();
    }
}
