<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CashFlowPermissionsSeeder extends Seeder
{
    public const NAMES = [
        'cash-flow.view',
        'cash-flow.manage',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::NAMES as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $perms = Permission::whereIn('name', self::NAMES)->get();

        foreach (['admin', 'contador'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
