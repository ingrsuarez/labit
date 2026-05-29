<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LabSampleDrawPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $view = Permission::findOrCreate('lab-sample-draws.view', 'web');
        $register = Permission::findOrCreate('lab-sample-draws.register', 'web');

        foreach (['admin', 'recepcion-lab', 'tecnico-lab', 'bioquimico'] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $role->givePermissionTo([$view, $register]);
        }
    }
}
