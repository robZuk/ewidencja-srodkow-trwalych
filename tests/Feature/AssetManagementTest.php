<?php

use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\TransferRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function editor(): User
{
    return User::factory()->create()->assignRole('editor');
}

function viewer(): User
{
    return User::factory()->create()->assignRole('viewer');
}

it('lists assets for authenticated users', function () {
    $asset = Asset::factory()->create(['name' => 'Mikroskop laboratoryjny']);

    actingAs(viewer());

    Volt::test('assets.index')
        ->assertSee('Mikroskop laboratoryjny')
        ->assertSee($asset->inventory_number);
});

it('filters assets by search term', function () {
    Asset::factory()->create(['name' => 'Drukarka biurowa']);
    Asset::factory()->create(['name' => 'Serwer rackowy']);

    actingAs(viewer());

    Volt::test('assets.index')
        ->set('search', 'Serwer')
        ->assertSee('Serwer rackowy')
        ->assertDontSee('Drukarka biurowa');
});

it('lets an editor create an asset and records an audit entry', function () {
    $field = InventoryField::factory()->create();

    actingAs(editor());

    Volt::test('assets.form')
        ->set('form.inventory_number', '001-002-03')
        ->set('form.name', 'Nowy laptop')
        ->set('form.inventory_field_id', $field->id)
        ->set('form.value', '4200.50')
        ->set('form.quantity', 1)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('assets.index'));

    $asset = Asset::where('inventory_number', '001-002-03')->first();

    expect($asset)->not->toBeNull()
        ->and($asset->name)->toBe('Nowy laptop');

    expect($asset->activities()->where('event', 'created')->exists())->toBeTrue();
});

it('enforces the unique inventory number within a field', function () {
    $field = InventoryField::factory()->create();
    Asset::factory()->create(['inventory_number' => 'DUP-1', 'inventory_field_id' => $field->id]);

    actingAs(editor());

    Volt::test('assets.form')
        ->set('form.inventory_number', 'DUP-1')
        ->set('form.name', 'Duplikat')
        ->set('form.inventory_field_id', $field->id)
        ->set('form.value', '10')
        ->set('form.quantity', 1)
        ->call('save')
        ->assertHasErrors('form.inventory_number');
});

it('forbids a viewer from creating an asset', function () {
    actingAs(viewer());

    Volt::test('assets.form')->assertForbidden();
});

it('allows only an admin to delete an asset', function () {
    $asset = Asset::factory()->create();
    $admin = User::factory()->create()->assignRole('admin');

    expect(editor()->can('delete', $asset))->toBeFalse()
        ->and($admin->can('delete', $asset))->toBeTrue();
});

it('starts a transfer straight from the assets list', function () {
    $target = InventoryField::factory()->create();
    $asset = Asset::factory()->create();

    actingAs(editor());

    Volt::test('assets.index')
        ->set('opsAssetId', $asset->id)
        ->set('opsTargetFieldId', $target->id)
        ->call('requestTransfer')
        ->assertHasNoErrors()
        ->assertDispatched('close-asset-ops');

    expect(TransferRequest::where('asset_id', $asset->id)->count())->toBe(1)
        ->and($asset->refresh()->isLockedForEditing())->toBeTrue();
});
