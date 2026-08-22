<?php

use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create()->assignRole('admin');
});

it('lists inventory fields for an admin', function () {
    InventoryField::factory()->create(['name' => 'Dziekanat']);

    actingAs($this->admin);

    Volt::test('inventory-fields.index')->assertSee('Dziekanat');
});

it('forbids non-admins from managing fields', function (string $role) {
    actingAs(User::factory()->create()->assignRole($role));

    Volt::test('inventory-fields.index')->assertForbidden();
})->with(['editor', 'inventory_section', 'viewer']);

it('creates an inventory field', function () {
    actingAs($this->admin);

    Volt::test('inventory-fields.form')
        ->set('form.code', '042')
        ->set('form.name', 'Nowe Pole')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('inventory-fields.index'));

    expect(InventoryField::where('code', '042')->where('name', 'Nowe Pole')->exists())->toBeTrue();
});

it('enforces a unique field code', function () {
    InventoryField::factory()->create(['code' => '001']);

    actingAs($this->admin);

    Volt::test('inventory-fields.form')
        ->set('form.code', '001')
        ->set('form.name', 'Duplikat')
        ->call('save')
        ->assertHasErrors('form.code');
});

it('deletes an empty field but not one with assets', function () {
    $empty = InventoryField::factory()->create();
    $withAssets = InventoryField::factory()->create();
    Asset::factory()->create(['inventory_field_id' => $withAssets->id]);

    expect($this->admin->can('delete', $empty))->toBeTrue()
        ->and($this->admin->can('delete', $withAssets))->toBeFalse();

    actingAs($this->admin);

    Volt::test('inventory-fields.index')->call('delete', $empty->id);

    expect(InventoryField::find($empty->id))->toBeNull();
});

it('shows a user only the fields they belong to', function () {
    $mine = InventoryField::factory()->create(['name' => 'Moje Pole']);
    $other = InventoryField::factory()->create(['name' => 'Cudze Pole']);

    $user = User::factory()->create()->assignRole('editor');
    $user->inventoryFields()->attach($mine);

    actingAs($user);

    Volt::test('inventory-fields.mine')
        ->assertSee('Moje Pole')
        ->assertDontSee('Cudze Pole');
});
