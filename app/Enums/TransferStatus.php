<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * State machine for a transfer / liquidation request.
 *
 * Transfer flow:   Pending → AcceptedByTarget → PendingInventory → Completed
 * Liquidation flow: PendingInventory → Completed
 * Any open state may transition to Rejected.
 */
enum TransferStatus: string
{
    case Pending = 'pending';                       // awaiting the target field
    case AcceptedByTarget = 'accepted_by_target';   // target accepted, awaiting inventory section
    case PendingInventory = 'pending_inventory';    // awaiting inventory section (liquidation starts here)
    case AcceptedByInventory = 'accepted_by_inventory';
    case Completed = 'completed';
    case Rejected = 'rejected';

    /** Human-readable Polish label used in the UI. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Oczekuje na pole docelowe',
            self::AcceptedByTarget => 'Zaakceptowane przez pole docelowe',
            self::PendingInventory => 'Oczekuje na Sekcję Inwentaryzacji',
            self::AcceptedByInventory => 'Zaakceptowane przez Inwentaryzację',
            self::Completed => 'Zakończone',
            self::Rejected => 'Odrzucone',
        };
    }

    /** Whether the request is still in progress (not resolved). */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed, self::Rejected], true);
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'green',
            self::Rejected => 'red',
            default => 'amber',
        };
    }
}
