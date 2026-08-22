<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\TransferRequest;
use App\Models\User;

/**
 * Step 1 of the transfer workflow: an editor requests moving an asset from its
 * current inventory field to a target field. The request then awaits acceptance.
 */
class RequestTransfer
{
    public function handle(Asset $asset, InventoryField $target, User $requester, ?string $note = null): TransferRequest
    {
        return TransferRequest::create([
            'type' => TransferType::Transfer,
            'status' => TransferStatus::Pending,
            'asset_id' => $asset->id,
            'asset_snapshot' => $this->snapshot($asset),
            'source_field_id' => $asset->inventory_field_id,
            'target_field_id' => $target->id,
            'requested_by' => $requester->id,
            'note' => $note,
        ]);
    }

    /** @return array<string, mixed> */
    private function snapshot(Asset $asset): array
    {
        return $asset->only(['inventory_number', 'name', 'value', 'asset_type']);
    }
}
