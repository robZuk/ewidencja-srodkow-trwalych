<?php

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\InventoryField;
use App\Models\TransferRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->targetField = InventoryField::factory()->create();

    $this->member = User::factory()->create()->assignRole('editor');
    $this->member->inventoryFields()->attach($this->targetField);

    $this->outsider = User::factory()->create()->assignRole('editor');
    $this->inventory = User::factory()->create()->assignRole('inventory_section');

    $this->request = TransferRequest::factory()->create([
        'type' => TransferType::Transfer,
        'status' => TransferStatus::Pending,
        'target_field_id' => $this->targetField->id,
    ]);
});

it('lets only a target-field member accept step 1', function () {
    expect($this->member->can('acceptTarget', $this->request))->toBeTrue()
        ->and($this->outsider->can('acceptTarget', $this->request))->toBeFalse()
        ->and($this->inventory->can('acceptTarget', $this->request))->toBeFalse();
});

it('reserves step 2 for the inventory section and only when pending inventory', function () {
    expect($this->inventory->can('acceptInventory', $this->request))->toBeFalse(); // still Pending

    $this->request->update(['status' => TransferStatus::PendingInventory]);

    expect($this->inventory->can('acceptInventory', $this->request->refresh()))->toBeTrue()
        ->and($this->member->can('acceptInventory', $this->request))->toBeFalse();
});

it('runs the two-role flow end to end through the UI', function () {
    // Step 1: the target-field member accepts.
    actingAs($this->member);
    Volt::test('transfers.index')->call('accept', $this->request->id)->assertHasNoErrors();
    expect($this->request->refresh()->status)->toBe(TransferStatus::PendingInventory);

    // Step 2: the inventory section confirms and the asset moves.
    actingAs($this->inventory);
    Volt::test('transfers.index')->call('accept', $this->request->id)->assertHasNoErrors();

    expect($this->request->refresh()->status)->toBe(TransferStatus::Completed)
        ->and($this->request->asset->refresh()->inventory_field_id)->toBe($this->targetField->id);
});

it('forbids the inventory section from doing step 1', function () {
    actingAs($this->inventory);

    Volt::test('transfers.index')->call('accept', $this->request->id)->assertForbidden();
});

it('lets a member reject step 1 but not an outsider', function () {
    actingAs($this->outsider);
    Volt::test('transfers.index')->call('reject', $this->request->id)->assertForbidden();

    actingAs($this->member);
    Volt::test('transfers.index')->call('reject', $this->request->id)->assertHasNoErrors();

    expect($this->request->refresh()->status)->toBe(TransferStatus::Rejected);
});
