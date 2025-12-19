<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EmployeeRoleSeeder extends Seeder
{
    /**
     * Crea el rol "empleado" con los permisos básicos del portal.
     */
    public function run(): void
    {
        // Limpiar caché de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos del Portal de Empleados
        $portalPermissions = [
            // Portal básico
            'portal.dashboard'   => 'Ver dashboard personal del portal',
            'portal.directory'   => 'Ver directorio de cumpleaños y vacaciones',
            
            // Portal supervisor
            'portal.team'        => 'Ver y gestionar equipo a cargo',
            'portal.team.leaves' => 'Ver solicitudes de licencia del equipo',
            
            // Vacaciones y licencias personales
            'vacation.view'      => 'Ver resumen de vacaciones propias',
            'vacation.request'   => 'Solicitar vacaciones',
            'leave.view.own'     => 'Ver licencias propias',
            'leave.request'      => 'Solicitar licencias',
            
            // Perfil
            'profile.view'       => 'Ver perfil propio',
            'profile.edit'       => 'Editar datos personales básicos',
        ];

        // Crear permisos si no existen
        foreach ($portalPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        // Crear rol "empleado"
        $employeeRole = Role::firstOrCreate(
            ['name' => 'empleado', 'guard_name' => 'web']
        );

        // Permisos básicos para todos los empleados
        $basicPermissions = [
            'portal.dashboard',
            'portal.directory',
            'vacation.view',
            'leave.view.own',
            'profile.view',
        ];

        // Asignar permisos básicos al rol empleado
        $employeeRole->syncPermissions(
            Permission::whereIn('name', $basicPermissions)->get()
        );

        // Crear rol "supervisor" si no existe
        $supervisorRole = Role::firstOrCreate(
            ['name' => 'supervisor', 'guard_name' => 'web']
        );

        // Permisos para supervisores (incluye los de empleado + adicionales)
        $supervisorPermissions = array_merge($basicPermissions, [
            'portal.team',
            'portal.team.leaves',
        ]);

        $supervisorRole->syncPermissions(
            Permission::whereIn('name', $supervisorPermissions)->get()
        );

        // Limpiar caché
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('✓ Rol "empleado" creado con ' . count($basicPermissions) . ' permisos');
        $this->command->info('✓ Rol "supervisor" creado con ' . count($supervisorPermissions) . ' permisos');
    }
}







