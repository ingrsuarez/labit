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

            // COMPRAS (Módulo de Compras)
            'compras.section',
            'suppliers.index',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            'supply-categories.index',
            'supply-categories.create',
            'supply-categories.edit',
            'supply-categories.delete',
            'supplies.index',
            'supplies.create',
            'supplies.edit',
            'supplies.delete',
            'stock-movements.index',
            'stock-movements.create',
            'purchase-quotation-requests.index',
            'purchase-quotation-requests.create',
            'purchase-quotation-requests.edit',
            'purchase-quotation-requests.delete',
            'purchase-orders.index',
            'purchase-orders.create',
            'purchase-orders.edit',
            'purchase-orders.delete',
            'delivery-notes.index',
            'delivery-notes.create',
            'delivery-notes.edit',
            'delivery-notes.delete',
            'purchase-invoices.index',
            'purchase-invoices.create',
            'purchase-invoices.edit',
            'purchase-invoices.delete',
            'payment-orders.index',
            'payment-orders.create',
            'payment-orders.edit',
            'payment-orders.delete',

            // VENTAS (Módulo de Ventas)
            'ventas.section',
            'sales-invoices.index',
            'sales-invoices.create',
            'sales-invoices.edit',
            'sales-invoices.delete',
            'collection-receipts.index',
            'collection-receipts.create',
            'collection-receipts.edit',
            'collection-receipts.delete',
            'points-of-sale.index',
            'points-of-sale.create',
            'points-of-sale.edit',
            'points-of-sale.delete',
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
            // Payroll
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
            
            // Salary Items
            'salary.index',
            'salary.create',
            'salary.edit',
            'salary.delete',
            
            // Empleados - Solo lectura
            'employee.show',

            // Compras - Acceso completo
            'compras.section',
            'suppliers.index',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            'supply-categories.index',
            'supply-categories.create',
            'supply-categories.edit',
            'supply-categories.delete',
            'supplies.index',
            'supplies.create',
            'supplies.edit',
            'supplies.delete',
            'stock-movements.index',
            'stock-movements.create',
            'purchase-quotation-requests.index',
            'purchase-quotation-requests.create',
            'purchase-quotation-requests.edit',
            'purchase-quotation-requests.delete',
            'purchase-orders.index',
            'purchase-orders.create',
            'purchase-orders.edit',
            'purchase-orders.delete',
            'delivery-notes.index',
            'delivery-notes.create',
            'delivery-notes.edit',
            'delivery-notes.delete',
            'purchase-invoices.index',
            'purchase-invoices.create',
            'purchase-invoices.edit',
            'purchase-invoices.delete',
            'payment-orders.index',
            'payment-orders.create',
            'payment-orders.edit',
            'payment-orders.delete',

            // Ventas - Acceso completo
            'ventas.section',
            'sales-invoices.index',
            'sales-invoices.create',
            'sales-invoices.edit',
            'sales-invoices.delete',
            'collection-receipts.index',
            'collection-receipts.create',
            'collection-receipts.edit',
            'collection-receipts.delete',
            'points-of-sale.index',
            'points-of-sale.create',
            'points-of-sale.edit',
            'points-of-sale.delete',
        ];

        $contadorRole->syncPermissions(Permission::whereIn('name', $contadorPermissions)->get());

        // 5.2) Crear rol compras (solo módulo de compras)
        $comprasRole = Role::firstOrCreate([
            'name'       => 'compras',
            'guard_name' => 'web',
        ]);

        $comprasPermissions = [
            'compras.section',
            'suppliers.index', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'supply-categories.index', 'supply-categories.create', 'supply-categories.edit', 'supply-categories.delete',
            'supplies.index', 'supplies.create', 'supplies.edit', 'supplies.delete',
            'stock-movements.index', 'stock-movements.create',
            'purchase-quotation-requests.index', 'purchase-quotation-requests.create', 'purchase-quotation-requests.edit', 'purchase-quotation-requests.delete',
            'purchase-orders.index', 'purchase-orders.create', 'purchase-orders.edit', 'purchase-orders.delete',
            'delivery-notes.index', 'delivery-notes.create', 'delivery-notes.edit', 'delivery-notes.delete',
            'purchase-invoices.index', 'purchase-invoices.create', 'purchase-invoices.edit', 'purchase-invoices.delete',
            'payment-orders.index', 'payment-orders.create', 'payment-orders.edit', 'payment-orders.delete',
        ];

        $comprasRole->syncPermissions(Permission::whereIn('name', $comprasPermissions)->get());

        // 5.3) Crear rol ventas (solo módulo de ventas)
        $ventasRole = Role::firstOrCreate([
            'name'       => 'ventas',
            'guard_name' => 'web',
        ]);

        $ventasPermissions = [
            'ventas.section',
            'sales-invoices.index', 'sales-invoices.create', 'sales-invoices.edit', 'sales-invoices.delete',
            'collection-receipts.index', 'collection-receipts.create', 'collection-receipts.edit', 'collection-receipts.delete',
            'points-of-sale.index', 'points-of-sale.create', 'points-of-sale.edit', 'points-of-sale.delete',
        ];

        $ventasRole->syncPermissions(Permission::whereIn('name', $ventasPermissions)->get());

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
