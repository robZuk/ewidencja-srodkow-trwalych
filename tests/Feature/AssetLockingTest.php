<?php

use App\Models\Asset;
use App\Models\TransferRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->editor = User::factory()->create()->assignRole('editor');
});

it('locks an asset with an open transfer request from editing and deletion', function () {
    $asset = Asset::factory()->create();
    TransferRequest::factory()->create(['asset_id' => $asset->id]); // Pending = open

    expect($asset->isLockedForEditing())->toBeTrue()
        ->and($this->editor->can('update', $asset))->toBeFalse()
        ->and($this->editor->can('delete', $asset))->toBeFalse();
});

it('unlocks an asset once its request is resolved', function () {
    $asset = Asset::factory()->create();
    TransferRequest::factory()->completed()->create(['asset_id' => $asset->id]);

    expect($asset->isLockedForEditing())->toBeFalse()
        ->and($this->editor->can('update', $asset))->toBeTrue();
});

it('redirects away from the edit form when the asset is locked', function () {
    $asset = Asset::factory()->create();
    TransferRequest::factory()->create(['asset_id' => $asset->id]);

    actingAs($this->editor);

    Volt::test('assets.form', ['asset' => $asset])
        ->assertRedirect(route('assets.index'));
});
