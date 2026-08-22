<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Enums\TransferStatus;
use App\Models\TransferRequest;
use App\Models\User;
use DomainException;

/**
 * Rejects any open transfer or liquidation request.
 */
class RejectRequest
{
    public function handle(TransferRequest $request, User $decider, ?string $note = null): TransferRequest
    {
        if (! $request->status->isOpen()) {
            throw new DomainException('Zgłoszenie zostało już rozstrzygnięte.');
        }

        $request->update([
            'status' => TransferStatus::Rejected,
            'inventory_accepted_by' => $decider->id,
            'resolved_at' => now(),
            'note' => $note ?? $request->note,
        ]);

        return $request;
    }
}
