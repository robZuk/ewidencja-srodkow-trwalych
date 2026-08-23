<?php

use App\Enums\AssetStatus;
use App\Enums\TransferStatus;
use App\Enums\TransferType;

it('exposes Polish labels for asset statuses', function () {
    expect(AssetStatus::Available->label())->toBe('Dostępny')
        ->and(AssetStatus::Liquidated->label())->toBe('Zlikwidowany')
        ->and(AssetStatus::Transferred->color())->toBe('amber');
});

it('exposes Polish labels for transfer types', function () {
    expect(TransferType::Transfer->label())->toBe('Przekazanie')
        ->and(TransferType::Liquidation->label())->toBe('Likwidacja');
});

it('knows which transfer statuses are still open', function () {
    expect(TransferStatus::Pending->isOpen())->toBeTrue()
        ->and(TransferStatus::PendingInventory->isOpen())->toBeTrue()
        ->and(TransferStatus::Completed->isOpen())->toBeFalse()
        ->and(TransferStatus::Rejected->isOpen())->toBeFalse();
});
