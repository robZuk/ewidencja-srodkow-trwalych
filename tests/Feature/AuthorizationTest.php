<?php

use App\Models\Asset;
use App\Models\TransferRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

dataset('roles and asset-management ability', [
    'admin can manage' => ['admin', true],
    'editor can manage' => ['editor', true],
    'inventory cannot manage' => ['inventory_section', false],
    'viewer cannot manage' => ['viewer', false],
]);

it('gates asset management by role', function (string $role, bool $allowed) {
    $user = User::factory()->create()->assignRole($role);

    expect($user->can('create', Asset::class))->toBe($allowed)
        ->and($user->can('view assets'))->toBeTrue();
})->with('roles and asset-management ability');

dataset('roles and decision ability', [
    'admin decides' => ['admin', true],
    'inventory decides' => ['inventory_section', true],
    'editor cannot decide' => ['editor', false],
    'viewer cannot decide' => ['viewer', false],
]);

it('gates transfer decisions by role', function (string $role, bool $allowed) {
    $user = User::factory()->create()->assignRole($role);
    $request = TransferRequest::factory()->create();

    expect($user->can('decide', $request))->toBe($allowed);
})->with('roles and decision ability');
