<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotente: crea permisos de percepciones de compra y los asigna a admin, contador y compras.
 */
class PurchasePerceptionsPermissionsSeeder extends Seeder
{
    public const NAMES = [
        'purchase-perceptions.index',
        'purchase-perceptions.create',
        'purchase-perceptions.edit',
        'purchase-perceptions.destroy',
        'purchase-perceptions.balances',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::NAMES as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        $allPerms = Permission::whereIn('name', self::NAMES)->get();

        $rolesPerms = [
            'admin'    => self::NAMES,
            'contador' => ['purchase-perceptions.index', 'purchase-perceptions.balances'],
            'compras'  => ['purchase-perceptions.index', 'purchase-perceptions.create', 'purchase-perceptions.edit'],
        ];

        foreach ($rolesPerms as $roleName => $permNames) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $perms = $allPerms->whereIn('name', $permNames);
                $role->givePermissionTo($perms);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
