<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RrhhProductivityPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('rrhh.productivity.view', 'web');

        foreach (['admin', 'contador'] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $role->givePermissionTo($permission);
        }
    }
}
