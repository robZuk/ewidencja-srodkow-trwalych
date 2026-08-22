<?php

use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->editor = User::factory()->create()->assignRole('editor');
});

it('lists inventory fields for a manager', function () {
    InventoryField::factory()->create(['name' => 'Dziekanat']);

    actingAs($this->editor);

    Volt::test('inventory-fields.index')->assertSee('Dziekanat');
});

it('forbids a viewer from managing fields', function () {
    actingAs(User::factory()->create()->assignRole('viewer'));

    Volt::test('inventory-fields.index')->assertForbidden();
});

it('creates an inventory field', function () {
    actingAs($this->editor);

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

    actingAs($this->editor);

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

    expect($this->editor->can('delete', $empty))->toBeTrue()
        ->and($this->editor->can('delete', $withAssets))->toBeFalse();

    actingAs($this->editor);

    Volt::test('inventory-fields.index')->call('delete', $empty->id);

    expect(InventoryField::find($empty->id))->toBeNull();
});
