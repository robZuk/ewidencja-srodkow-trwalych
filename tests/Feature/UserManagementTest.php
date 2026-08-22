<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create()->assignRole('admin');
});

it('lists users for an admin', function () {
    $other = User::factory()->create(['name' => 'Jan Kowalski'])->assignRole('editor');

    actingAs($this->admin);

    Volt::test('users.index')->assertSee('Jan Kowalski');
});

it('forbids a non-admin from user administration', function () {
    actingAs(User::factory()->create()->assignRole('editor'));

    Volt::test('users.index')->assertForbidden();
});

it('creates a user with a role', function () {
    actingAs($this->admin);

    Volt::test('users.form')
        ->set('form.name', 'Nowy Pracownik')
        ->set('form.email', 'nowy@example.com')
        ->set('form.role', 'inventory_section')
        ->set('form.password', 'secret123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    $user = User::where('email', 'nowy@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('inventory_section'))->toBeTrue();
});

it('prevents an admin from deleting themselves', function () {
    actingAs($this->admin);

    expect($this->admin->can('delete', $this->admin))->toBeFalse();
});

it('scopes impersonation: not self, not another admin', function () {
    $viewer = User::factory()->create()->assignRole('viewer');
    $admin2 = User::factory()->create()->assignRole('admin');

    expect($this->admin->can('impersonate', $viewer))->toBeTrue()
        ->and($this->admin->can('impersonate', $admin2))->toBeFalse()
        ->and($this->admin->can('impersonate', $this->admin))->toBeFalse();
});

it('takes over and restores a session', function () {
    $viewer = User::factory()->create()->assignRole('viewer');

    actingAs($this->admin);

    get(route('impersonate.start', $viewer))->assertRedirect(route('assets.index'));
    expect(auth()->id())->toBe($viewer->id)
        ->and(session('impersonator_id'))->toBe($this->admin->id);

    get(route('impersonate.stop'))->assertRedirect(route('users.index'));
    expect(auth()->id())->toBe($this->admin->id)
        ->and(session()->has('impersonator_id'))->toBeFalse();
});

it('forbids impersonating an administrator over HTTP', function () {
    $admin2 = User::factory()->create()->assignRole('admin');

    actingAs($this->admin);

    get(route('impersonate.start', $admin2))->assertForbidden();
});
