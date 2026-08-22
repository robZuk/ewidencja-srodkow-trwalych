<?php

declare(strict_types=1);

namespace App\Enums;

enum TransferType: string
{
    case Transfer = 'transfer';
    case Liquidation = 'liquidation';

    /** Human-readable Polish label used in the UI. */
    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'Przekazanie środka',
            self::Liquidation => 'Wniosek o likwidację',
        };
    }
}
