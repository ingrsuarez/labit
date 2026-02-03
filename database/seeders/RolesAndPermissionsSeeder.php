<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                // 1) Limpiar caché de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2) Nombres de permisos (según tus rutas)
        $permissions = [
            // PATIENT
            'patient.index',
            'patient.show',
            'patient.edit',
            'patient.save',
            'patient.store',

            // TESTS
            'tests.index',
            'test.store',

            // ADMISSION
            'admission.index',
            'admission.store',

            // INSURANCE
            'insurance.index',
            'insurance.store',

            // GROUP
            'group.index',
            'group.store',

            // MANAGEMENT
            'manage.index',
            'view.chart',

            // EMPLOYEES
            'employee.new',
            'employee.store',
            'employee.edit',
            'employee.save',
            'employee.show',

            // JOBS
            'job.new',
            'job.store',
            'job.edit',
            'job.save',
            'job.delete',
            'job.detach',

            // CATEGORY (Jobs)
            'category.new',
            'category.store',
            'category.edit',
            'category.save',
            'category.delete',

            // LEAVES
            'leave.resume',
            'leave.new',
            'leave.store',
            'leave.update',
            'leave.edit',
            'leave.delete',

            // USERS
            'user.index',
            'user.edit',
            'user.save',
            'role.attach',
            'role.detach',

            // ROLES
            'role.new',
            'role.store',
            'role.update',
            'role.edit',
            'role.attachPermission',
            'role.detachPermission',

            // PERMISSIONS
            'permission.new',
            'permission.store',
            'permission.edit',

            // SAMPLES/PROTOCOLOS
            'samples.index',
            'samples.create',
            'samples.edit',
            'samples.delete',
            'samples.loadResults',
            'samples.validate',      // Permiso para validar protocolos
            'samples.downloadPdf',
            'samples.sendEmail',

            // CUSTOMERS
            'customers.index',
            'customers.create',
            'customers.edit',
            'customers.delete',

            // PAYROLL (Liquidación de Sueldos)
            'payroll.index',
            'payroll.show',
            'payroll.store',
            'payroll.closed',
            'payroll.pdf',
            'payroll.bulkPdf',
            'payroll.liquidar',
            'payroll.pagar',
            'payroll.reabrir',
            'payroll.delete',
            'payroll.bulk',
            'payroll.sac',

            // SALARY ITEMS (Conceptos de Liquidación)
            'salary.index',
            'salary.create',
            'salary.edit',
            'salary.delete',
        ];

        // 3) Crear/asegurar permisos (idempotente)
        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        // 4) Crear/asegurar rol admin
        $adminRole = Role::firstOrCreate([
            'name'       => 'admin',
            'guard_name' => 'web',
        ]);

        // 5) Asignar todos los permisos al rol admin
        $adminRole->syncPermissions(Permission::whereIn('name', $permissions)->get());

        // 5.1) Crear rol contador con permisos específicos
        $contadorRole = Role::firstOrCreate([
            'name'       => 'contador',
            'guard_name' => 'web',
        ]);

        // Permisos del contador: liquidaciones, historial, PDFs y conceptos
        $contadorPermissions = [
            // Payroll - Ver y generar
            'payroll.index',
            'payroll.show',
            'payroll.store',
            'payroll.closed',
            'payroll.pdf',
            'payroll.bulkPdf',
            'payroll.bulk',
            'payroll.sac',
            'payroll.liquidar',
            'payroll.pagar',
            
            // Salary Items - Gestión completa de conceptos
            'salary.index',
            'salary.create',
            'salary.edit',
            'salary.delete',
            
            // Empleados - Solo lectura para ver datos en liquidaciones
            'employee.show',
        ];

        $contadorRole->syncPermissions(Permission::whereIn('name', $contadorPermissions)->get());

        // 6) Crear/asegurar usuario admin (lee de .env con defaults)
        $email    = env('ADMIN_EMAIL', 'admin@admin');
        $password = env('ADMIN_PASSWORD', 'Rodrigoo'); // cambia en prod

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => 'Administrador',
                'password' => Hash::make($password),
            ]
        );

        if (! $user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        // 7) Limpiar caché nuevamente por las dudas
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    
    
    }
}
