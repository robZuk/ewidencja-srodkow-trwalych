<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Enums\AssetStatus;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\TransferRequest;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Final step of a liquidation: the inventory section confirms, the asset is
 * marked liquidated and its liquidation date is stamped.
 */
class AcceptLiquidation
{
    public function handle(TransferRequest $request, User $decider): TransferRequest
    {
        if ($request->type !== TransferType::Liquidation || $request->status !== TransferStatus::PendingInventory) {
            throw new DomainException('Nie można zatwierdzić likwidacji w tym stanie.');
        }

        DB::transaction(function () use ($request, $decider): void {
            $request->asset?->update([
                'status' => AssetStatus::Liquidated,
                'liquidation_date' => now(),
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
