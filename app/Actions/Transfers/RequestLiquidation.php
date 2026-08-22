<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Asset;
use App\Models\TransferRequest;
use App\Models\User;

/**
 * Opens a liquidation request for an asset. Liquidation skips the target-field
 * step and goes straight to the inventory section for a decision.
 */
class RequestLiquidation
{
    public function handle(Asset $asset, User $requester, ?string $note = null): TransferRequest
    {
        return TransferRequest::create([
            'type' => TransferType::Liquidation,
            'status' => TransferStatus::PendingInventory,
            'asset_id' => $asset->id,
            'asset_snapshot' => $asset->only(['inventory_number', 'name', 'value', 'asset_type']),
            'source_field_id' => $asset->inventory_field_id,
            'requested_by' => $requester->id,
            'note' => $note,
        ]);
    }
}
