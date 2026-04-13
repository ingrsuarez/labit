<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotente: crea permisos de servicios de compra y los asigna a admin, contador y compras.
 * Útil si ya corriste RolesAndPermissionsSeeder antes de existir estos permisos.
 */
class PurchaseServicesPermissionsSeeder extends Seeder
{
    public const NAMES = [
        'purchase-service-categories.index',
        'purchase-service-categories.create',
        'purchase-service-categories.edit',
        'purchase-service-categories.delete',
        'purchase-services.index',
        'purchase-services.create',
        'purchase-services.edit',
        'purchase-services.delete',
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

        $permissions = Permission::whereIn('name', self::NAMES)->get();

        foreach (['admin', 'contador', 'compras'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
