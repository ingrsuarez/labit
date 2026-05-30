<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del módulo de declaraciones juradas de impuestos (v1.64.0).
 */
class TaxDeclarationsPermissionsSeeder extends Seeder
{
    public const NAMES = [
        'taxes.manage',
        'tax-returns.manage',
        'form931.manage',
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
