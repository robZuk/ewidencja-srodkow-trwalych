<?php

declare(strict_types=1);

namespace App\Enums;

enum AssetStatus: string
{
    case Available = 'available';
    case Transferred = 'transferred';
    case Liquidated = 'liquidated';

    /** Human-readable Polish label used in the UI. */
    public function label(): string
    {
        return match ($this) {
            self::Available => 'Dostępny',
            self::Transferred => 'Przekazany',
            self::Liquidated => 'Zlikwidowany',
        };
    }

    /** Tailwind colour token used by status badges. */
    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::Transferred => 'amber',
            self::Liquidated => 'red',
        };
    }
}
