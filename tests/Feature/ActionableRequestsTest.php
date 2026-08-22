<?php

use App\Models\InventoryField;
use App\Models\TransferRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('counts pending-inventory requests for the inventory section', function () {
    TransferRequest::factory()->liquidation()->count(2)->create();   // PendingInventory
    TransferRequest::factory()->create();                            // Pending (step 1)
    TransferRequest::factory()->completed()->create();               // resolved

    $inventory = User::factory()->create()->assignRole('inventory_section');

    expect(TransferRequest::actionableBy($inventory)->count())->toBe(2);
});

it('counts step-1 transfers only for a member of the target field', function () {
    $field = InventoryField::factory()->create();

    TransferRequest::factory()->create(['target_field_id' => $field->id]);        // to my field
    TransferRequest::factory()->create();                                        // to another field
    TransferRequest::factory()->liquidation()->create();                         // inventory step

    $member = User::factory()->create()->assignRole('editor');
    $member->inventoryFields()->attach($field);

    expect(TransferRequest::actionableBy($member)->count())->toBe(1);
});

it('counts nothing for a viewer', function () {
    TransferRequest::factory()->count(3)->create();
    TransferRequest::factory()->liquidation()->create();

    $viewer = User::factory()->create()->assignRole('viewer');

    expect(TransferRequest::actionableBy($viewer)->count())->toBe(0);
});

it('counts all open requests for an admin', function () {
    TransferRequest::factory()->count(2)->create();          // Pending
    TransferRequest::factory()->liquidation()->create();     // PendingInventory
    TransferRequest::factory()->completed()->create();       // resolved (excluded)

    $admin = User::factory()->create()->assignRole('admin');

    expect(TransferRequest::actionableBy($admin)->count())->toBe(3);
});
