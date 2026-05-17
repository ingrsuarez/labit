<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotente: crea permisos de eliminación de prácticas/protocolos (v1.77.0)
 * y los asigna con givePermissionTo (no pisa permisos custom en producción).
 *
 * Uso: php artisan db:seed --class=LabAdmissionsDeletePermissionsSeeder
 */
class LabAdmissionsDeletePermissionsSeeder extends Seeder
{
    public const LAB_DELETE = 'lab-admissions.delete';

    public const VET_DELETE = 'vet-admissions.delete';

    public const SAMPLES_DELETE = 'samples.delete';

    /** @var list<string> */
    public const NAMES = [
        self::LAB_DELETE,
        self::VET_DELETE,
        self::SAMPLES_DELETE,
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

        $allPerms = Permission::whereIn('name', self::NAMES)->get();

        $rolesPerms = [
            'admin' => self::NAMES,
            'recepcion-lab' => self::NAMES,
            'bioquimico' => self::NAMES,
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
