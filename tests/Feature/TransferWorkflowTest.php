<?php

use App\Actions\Transfers\AcceptLiquidation;
use App\Actions\Transfers\AcceptTransfer;
use App\Actions\Transfers\RejectRequest;
use App\Actions\Transfers\RequestLiquidation;
use App\Actions\Transfers\RequestTransfer;
use App\Enums\AssetStatus;
use App\Enums\TransferStatus;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\TransferRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->editor = User::factory()->create()->assignRole('editor');
    $this->inventory = User::factory()->create()->assignRole('inventory_section');
});

it('runs the full transfer approval flow and moves the asset', function () {
    $source = InventoryField::factory()->create();
    $target = InventoryField::factory()->create();
    $asset = Asset::factory()->create(['inventory_field_id' => $source->id]);

    $request = app(RequestTransfer::class)->handle($asset, $target, $this->editor);
    expect($request->status)->toBe(TransferStatus::Pending);

    app(AcceptTransfer::class)->handle($request, $this->inventory);
    expect($request->refresh()->status)->toBe(TransferStatus::PendingInventory);

    app(AcceptTransfer::class)->handle($request, $this->inventory);

    expect($request->refresh()->status)->toBe(TransferStatus::Completed)
        ->and($asset->refresh()->inventory_field_id)->toBe($target->id);
});

it('completes a liquidation and marks the asset liquidated', function () {
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $request = app(RequestLiquidation::class)->handle($asset, $this->editor);
    expect($request->status)->toBe(TransferStatus::PendingInventory);

    app(AcceptLiquidation::class)->handle($request, $this->inventory);

    expect($request->refresh()->status)->toBe(TransferStatus::Completed)
        ->and($asset->refresh()->status)->toBe(AssetStatus::Liquidated)
        ->and($asset->liquidation_date)->not->toBeNull();
});

it('rejects an open request', function () {
    $request = TransferRequest::factory()->create();

    app(RejectRequest::class)->handle($request, $this->inventory);

    expect($request->refresh()->status)->toBe(TransferStatus::Rejected);
});

it('cannot reject an already resolved request', function () {
    $request = TransferRequest::factory()->completed()->create();

    app(RejectRequest::class)->handle($request, $this->inventory);
})->throws(DomainException::class);

it('lets the inventory section accept via the UI', function () {
    $request = TransferRequest::factory()->liquidation()->create();

    actingAs($this->inventory);

    Volt::test('transfers.index')
        ->call('accept', $request->id)
        ->assertHasNoErrors();

    expect($request->refresh()->status)->toBe(TransferStatus::Completed);
});

it('forbids an editor from deciding requests', function () {
    $request = TransferRequest::factory()->create();

    actingAs($this->editor);

    Volt::test('transfers.index')
        ->call('accept', $request->id)
        ->assertForbidden();
});
