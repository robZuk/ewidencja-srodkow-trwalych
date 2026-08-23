<?php

use App\Models\Activity;
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

it('logs create, update and delete with the causer', function () {
    actingAs($this->admin);

    $field = InventoryField::factory()->create(['name' => 'Stara nazwa']);

    $created = Activity::where('subject_type', InventoryField::class)
        ->where('subject_id', $field->id)
        ->where('event', 'created')
        ->first();

    expect($created)->not->toBeNull()
        ->and($created->causer_id)->toBe($this->admin->id);

    $field->update(['name' => 'Nowa nazwa']);

    $updated = Activity::where('subject_id', $field->id)->where('event', 'updated')->latest()->first();
    expect($updated->properties)->toHaveKey('name')
        ->and($updated->properties['name']['old'])->toBe('Stara nazwa')
        ->and($updated->properties['name']['new'])->toBe('Nowa nazwa');

    $field->delete();
    expect(Activity::where('subject_id', $field->id)->where('event', 'deleted')->exists())->toBeTrue();
});

it('logs asset changes to the system activity log', function () {
    actingAs($this->admin);

    $asset = Asset::factory()->create();

    expect(Activity::where('subject_type', Asset::class)->where('subject_id', $asset->id)->where('event', 'created')->exists())->toBeTrue();
});

it('does not log ignored/sensitive fields', function () {
    actingAs($this->admin);

    $user = User::factory()->create();
    Activity::query()->delete();

    $user->update(['password' => 'brand-new-secret']);

    // Password-only change produces no meaningful activity entry.
    expect(Activity::where('subject_id', $user->id)->where('event', 'updated')->exists())->toBeFalse();
});

it('lets an admin view the activity log', function () {
    actingAs($this->admin);
    InventoryField::factory()->create(['name' => 'Widoczne Pole']);

    Volt::test('activity-log.index')->assertSee('Widoczne Pole');
});

it('forbids non-admins from the activity log', function (string $role) {
    actingAs(User::factory()->create()->assignRole($role));

    Volt::test('activity-log.index')->assertForbidden();
})->with(['editor', 'inventory_section', 'viewer']);
