<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    private const PERMISSIONS = [
        'view assets',
        'manage assets',            // create / update / delete assets
        'request transfers',        // initiate a transfer or liquidation
        'decide transfers',         // accept/reject as target field or inventory section
        'view users',               // read the user list
        'manage users',             // user administration + impersonation (admin only)
        'view inventory fields',    // read the full list of pola spisowe
        'manage inventory fields',  // create/edit/delete pola spisowe (admin only)
        'view activity log',        // system-wide change history (admin only)
    ];

    /** @var array<string, list<string>> */
    private const ROLES = [
        'admin' => self::PERMISSIONS,
        'editor' => ['view assets', 'manage assets', 'request transfers'],
        'inventory_section' => ['view assets', 'request transfers', 'decide transfers', 'view inventory fields', 'view users'],
        'viewer' => ['view assets'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role)->syncPermissions($permissions);
        }
    }
}
