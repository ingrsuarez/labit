<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserNavigationStat;
use Illuminate\Support\Collection;

class NavigationCatalog
{
    /** @var array<string, string>|null */
    private static ?array $routeToKeyMap = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function shortcuts(): array
    {
        return [
            // Admin / finanzas
            [
                'key' => 'financial-summary',
                'name' => 'Resumen financiero',
                'description' => 'KPIs de ventas, compras, ingresos y egresos',
                'route' => route('dashboard.financial'),
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'roles' => ['admin', 'contador'],
                'default_priority' => 1,
                'route_names' => ['dashboard.financial'],
            ],
            [
                'key' => 'purchases-hub',
                'name' => 'Compras',
                'description' => 'Hub de proveedores, insumos y órdenes',
                'route' => route('purchases.section'),
                'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z',
                'permission' => 'compras.section',
                'default_priority' => 10,
                'hub' => true,
            ],
            [
                'key' => 'purchase-invoices',
                'name' => 'Facturas de Compra',
                'description' => 'Facturas y saldos de proveedores',
                'route' => route('purchase-invoices.index'),
                'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                'permission' => 'purchase-invoices.index',
                'default_priority' => 11,
                'route_names' => self::crudRoutes('purchase-invoices'),
            ],
            [
                'key' => 'suppliers',
                'name' => 'Proveedores',
                'description' => 'Gestión de proveedores',
                'route' => route('suppliers.index'),
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'permission' => 'compras.section',
                'default_priority' => 12,
                'route_names' => self::crudRoutes('suppliers'),
            ],
            [
                'key' => 'delivery-notes',
                'name' => 'Remitos',
                'description' => 'Recepción de mercadería',
                'route' => route('delivery-notes.index'),
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                'permission' => 'delivery-notes.index',
                'default_priority' => 13,
                'route_names' => self::crudRoutes('delivery-notes'),
            ],
            [
                'key' => 'supplies',
                'name' => 'Insumos',
                'description' => 'Catálogo de insumos',
                'route' => route('supplies.index'),
                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                'permission' => 'supplies.index',
                'default_priority' => 13,
                'route_names' => self::crudRoutes('supplies'),
            ],
            [
                'key' => 'stock-movements',
                'name' => 'Movimientos de Stock',
                'description' => 'Entradas y salidas de inventario',
                'route' => route('stock-movements.index'),
                'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4',
                'permission' => 'stock-movements.index',
                'default_priority' => 14,
                'route_names' => ['stock-movements.index', 'stock-movements.create'],
            ],
            [
                'key' => 'payment-orders',
                'name' => 'Órdenes de Pago',
                'description' => 'Pagos a proveedores',
                'route' => route('payment-orders.index'),
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'permission' => 'payment-orders.index',
                'default_priority' => 15,
                'route_names' => self::crudRoutes('payment-orders'),
            ],
            [
                'key' => 'sales-hub',
                'name' => 'Ventas',
                'description' => 'Hub de facturación y cobranzas',
                'route' => route('sales.section'),
                'icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
                'permission' => 'ventas.section',
                'default_priority' => 20,
                'hub' => true,
            ],
            [
                'key' => 'sales-invoices',
                'name' => 'Facturas de Venta',
                'description' => 'Facturación y saldos de clientes',
                'route' => route('sales-invoices.index'),
                'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                'permission' => 'sales-invoices.index',
                'default_priority' => 21,
                'route_names' => self::crudRoutes('sales-invoices'),
            ],
            [
                'key' => 'quotes',
                'name' => 'Presupuestos',
                'description' => 'Presupuestos y cotizaciones',
                'route' => route('quotes.index'),
                'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                'permission' => 'sales-invoices.index',
                'default_priority' => 22,
                'route_names' => self::crudRoutes('quotes'),
            ],
            [
                'key' => 'collection-receipts',
                'name' => 'Recibos de Cobro',
                'description' => 'Cobranzas a clientes',
                'route' => route('collection-receipts.index'),
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'permission' => 'collection-receipts.index',
                'default_priority' => 23,
                'route_names' => self::crudRoutes('collection-receipts'),
            ],
            [
                'key' => 'customers',
                'name' => 'Clientes',
                'description' => 'Gestión de clientes',
                'route' => route('customer.index'),
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'permission' => 'customers.index',
                'default_priority' => 24,
                'route_names' => ['customer.index', 'customer.create', 'customer.edit'],
            ],
            [
                'key' => 'rrhh-hub',
                'name' => 'Recursos Humanos',
                'description' => 'Personal, licencias y liquidaciones',
                'route' => route('rrhh.index'),
                'icon' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0M9 14a2 2 0 100-4 2 2 0 000 4zm0 0c-1.5 0-3 .5-3 2v1h6v-1c0-1.5-1.5-2-3-2z',
                'visible' => fn (User $user) => RrhhNavigation::userCanAccessHub($user),
                'default_priority' => 30,
                'hub' => true,
            ],
            [
                'key' => 'rrhh-employees',
                'name' => 'Empleados',
                'description' => 'Listado y gestión de empleados',
                'route' => route('employee.show'),
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'permission' => 'personal.section',
                'default_priority' => 31,
                'route_names' => ['employee.show', 'employee.create', 'employee.edit'],
            ],
            [
                'key' => 'rrhh-productivity',
                'name' => 'Productividad diaria',
                'description' => 'KPIs de productividad por empleado y puesto',
                'route' => route('rrhh.productividad'),
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'permission' => 'rrhh.productivity.view',
                'default_priority' => 32,
                'route_names' => ['rrhh.productividad'],
            ],
            [
                'key' => 'rrhh-vacations',
                'name' => 'Vacaciones',
                'description' => 'Solicitar y gestionar vacaciones',
                'route' => route('vacation.index'),
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                'permission' => 'ausencias.section',
                'default_priority' => 33,
                'route_names' => ['vacation.index', 'vacation.approval', 'vacation.calendar', 'vacation.holidays'],
            ],
            [
                'key' => 'rrhh-payroll',
                'name' => 'Generar Recibos',
                'description' => 'Crear recibos de sueldo',
                'route' => route('payroll.index'),
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'permission' => 'liquidaciones.section',
                'default_priority' => 34,
                'route_names' => ['payroll.index', 'payroll.closed', 'payroll.settings'],
            ],
            [
                'key' => 'rrhh-leaves',
                'name' => 'Gestionar Licencias',
                'description' => 'Crear y editar licencias',
                'route' => route('leave.index'),
                'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                'permission' => 'ausencias.section',
                'default_priority' => 35,
                'route_names' => ['leave.index', 'leave.resume'],
            ],
            [
                'key' => 'accounting-hub',
                'name' => 'Contabilidad',
                'description' => 'Plan de cuentas, asientos y conciliación',
                'route' => route('accounting.section'),
                'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                'permission' => 'contabilidad.section',
                'default_priority' => 40,
                'hub' => true,
            ],
            [
                'key' => 'accounting-accounts',
                'name' => 'Plan de Cuentas',
                'description' => 'Estructura de cuentas contables',
                'route' => route('accounting.accounts.index'),
                'icon' => 'M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
                'permission' => 'contabilidad.section',
                'default_priority' => 41,
                'route_names' => self::crudRoutes('accounting.accounts'),
            ],
            [
                'key' => 'accounting-bank',
                'name' => 'Conciliación Bancaria',
                'description' => 'Cuentas bancarias y extractos',
                'route' => route('accounting.bank-accounts.index'),
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                'permission' => 'contabilidad.section',
                'default_priority' => 42,
                'route_names' => ['accounting.bank-accounts.index', 'accounting.bank-accounts.create', 'accounting.bank-accounts.show'],
            ],
            [
                'key' => 'accounting-journal',
                'name' => 'Libro Diario',
                'description' => 'Registro cronológico de asientos',
                'route' => route('accounting.journal.index'),
                'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                'permission' => 'contabilidad.section',
                'default_priority' => 43,
                'route_names' => ['accounting.journal.index', 'accounting.journal.create', 'accounting.journal.show'],
            ],
            [
                'key' => 'accounting-ledger',
                'name' => 'Libro Mayor',
                'description' => 'Movimientos por cuenta',
                'route' => route('accounting.ledger'),
                'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'permission' => 'contabilidad.section',
                'default_priority' => 44,
                'route_names' => ['accounting.ledger'],
            ],
            [
                'key' => 'companies',
                'name' => 'Empresas',
                'description' => 'Gestión de empresas del sistema',
                'route' => route('companies.index'),
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'permission' => 'companies.section',
                'default_priority' => 45,
                'sidebar' => true,
            ],
            [
                'key' => 'audit',
                'name' => 'Auditoría',
                'description' => 'Registro de acciones del sistema',
                'route' => route('audit.index'),
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                'permission' => 'auditoria.section',
                'default_priority' => 46,
                'sidebar' => true,
            ],

            // Laboratorio
            [
                'key' => 'lab-dashboard',
                'name' => 'Dashboard Laboratorio',
                'description' => 'KPIs operativos del laboratorio',
                'route' => route('lab.dashboard'),
                'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
                'permission' => 'lab.section',
                'default_priority' => 50,
                'hub' => true,
            ],
            [
                'key' => 'lab-admissions',
                'name' => 'Protocolos Clínicos',
                'description' => 'Listado de protocolos clínicos',
                'route' => route('lab.admissions.index'),
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                'permission' => 'lab-admissions.index',
                'default_priority' => 51,
                'route_names' => array_merge(
                    ['lab.admissions.index', 'lab.admissions.create', 'lab.admissions.show', 'lab.admissions.edit', 'lab.admissions.pending-results'],
                    self::crudRoutes('lab.admissions', false)
                ),
            ],
            [
                'key' => 'vet-admissions',
                'name' => 'Protocolos Veterinarios',
                'description' => 'Listado de protocolos veterinarios',
                'route' => route('vet.admissions.index'),
                'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
                'permission' => 'lab-admissions.index',
                'default_priority' => 52,
                'route_names' => ['vet.admissions.index', 'vet.admissions.create', 'vet.admissions.show', 'vet.admissions.edit'],
            ],
            [
                'key' => 'samples',
                'name' => 'Protocolos de Muestras',
                'description' => 'Aguas y alimentos',
                'route' => route('sample.index'),
                'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
                'permission' => 'samples.index',
                'default_priority' => 53,
                'route_names' => ['sample.index', 'sample.create', 'sample.show', 'sample.edit'],
            ],
            [
                'key' => 'patients',
                'name' => 'Pacientes',
                'description' => 'Gestión de pacientes',
                'route' => route('patient.index'),
                'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                'permission' => 'patient.index',
                'default_priority' => 54,
                'route_names' => ['patient.index'],
            ],
            [
                'key' => 'lab-reports',
                'name' => 'Reportes',
                'description' => 'Informes y estadísticas del laboratorio',
                'route' => route('lab.reports.monthly'),
                'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'permission' => 'lab-reports.index',
                'default_priority' => 55,
                'route_names' => ['lab.reports.monthly'],
            ],

            // Portal empleado
            [
                'key' => 'portal-payslips',
                'name' => 'Recibos de Sueldo',
                'description' => 'Mis recibos de sueldo',
                'route' => route('portal.payslips'),
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'visible' => fn (User $user) => (bool) $user->employee,
                'default_priority' => 60,
                'route_names' => ['portal.payslips', 'portal.payslips.download'],
            ],
            [
                'key' => 'portal-requests',
                'name' => 'Mis Solicitudes',
                'description' => 'Vacaciones y licencias',
                'route' => route('portal.requests'),
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                'visible' => fn (User $user) => (bool) $user->employee,
                'default_priority' => 61,
                'route_names' => ['portal.requests'],
            ],
            [
                'key' => 'portal-circulars',
                'name' => 'Circulares',
                'description' => 'Comunicaciones internas',
                'route' => route('portal.circulars.index'),
                'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
                'visible' => fn (User $user) => (bool) $user->employee,
                'default_priority' => 62,
                'route_names' => ['portal.circulars.index', 'portal.circulars.show'],
            ],
            [
                'key' => 'portal-directory',
                'name' => 'Directorio',
                'description' => 'Equipo y contactos',
                'route' => route('portal.directory'),
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'visible' => fn (User $user) => (bool) $user->employee,
                'default_priority' => 63,
                'route_names' => ['portal.directory', 'portal.organization'],
            ],
            [
                'key' => 'portal-team',
                'name' => 'Mi Equipo',
                'description' => 'Gestión de mi equipo',
                'route' => route('portal.team'),
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'visible' => fn (User $user) => $user->employee?->isSupervisor() ?? false,
                'default_priority' => 64,
                'route_names' => ['portal.team'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function shortcut(string $key): ?array
    {
        foreach (self::shortcuts() as $shortcut) {
            if ($shortcut['key'] === $key) {
                return $shortcut;
            }
        }

        return null;
    }

    public static function shortcutKeyForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        if (self::$routeToKeyMap === null) {
            self::$routeToKeyMap = [];
            foreach (self::shortcuts() as $shortcut) {
                foreach ($shortcut['route_names'] ?? [] as $name) {
                    self::$routeToKeyMap[$name] = $shortcut['key'];
                }
            }
        }

        return self::$routeToKeyMap[$routeName] ?? null;
    }

    /**
     * @return array<int, array{name: string, description: string, route: string, icon: string}>
     */
    public static function shortcutsForUser(User $user, int $limit = 12): array
    {
        $visible = collect(self::shortcuts())
            ->filter(fn (array $shortcut) => self::isQuickAccessShortcut($shortcut))
            ->filter(fn (array $shortcut) => self::userCanSeeShortcut($user, $shortcut))
            ->values();

        return self::formatSortedShortcuts($user, $visible, $limit);
    }

    /**
     * @return array<int, array{name: string, description: string, route: string, icon: string}>
     */
    public static function shortcutsForPortalUser(User $user, int $limit = 8): array
    {
        $visible = collect(self::shortcuts())
            ->filter(fn (array $shortcut) => str_starts_with($shortcut['key'], 'portal-'))
            ->filter(fn (array $shortcut) => self::userCanSeeShortcut($user, $shortcut))
            ->values();

        return self::formatSortedShortcuts($user, $visible, $limit);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $visible
     * @return array<int, array{name: string, description: string, route: string, icon: string}>
     */
    private static function formatSortedShortcuts(User $user, Collection $visible, int $limit): array
    {
        if ($visible->isEmpty()) {
            return [];
        }

        $keys = $visible->pluck('key')->all();

        /** @var Collection<string, UserNavigationStat> $stats */
        $stats = UserNavigationStat::query()
            ->where('user_id', $user->id)
            ->whereIn('shortcut_key', $keys)
            ->get()
            ->keyBy('shortcut_key');

        return $visible
            ->sort(function (array $a, array $b) use ($stats) {
                $hitA = $stats->get($a['key'])?->hit_count ?? 0;
                $hitB = $stats->get($b['key'])?->hit_count ?? 0;

                if ($hitA !== $hitB) {
                    return $hitB <=> $hitA;
                }

                $lastA = $stats->get($a['key'])?->last_accessed_at;
                $lastB = $stats->get($b['key'])?->last_accessed_at;

                if ($lastA && $lastB && $lastA->ne($lastB)) {
                    return $lastB <=> $lastA;
                }

                return ($a['default_priority'] ?? 999) <=> ($b['default_priority'] ?? 999);
            })
            ->take($limit)
            ->map(fn (array $shortcut) => [
                'name' => $shortcut['name'],
                'description' => $shortcut['description'],
                'route' => $shortcut['route'],
                'icon' => $shortcut['icon'],
            ])
            ->values()
            ->all();
    }

    /**
     * Accesos rápidos del home: cards de sección (segundo nivel), no ítems del sidebar.
     *
     * @param  array<string, mixed>  $shortcut
     */
    public static function isQuickAccessShortcut(array $shortcut): bool
    {
        return empty($shortcut['hub']) && empty($shortcut['sidebar']);
    }

    /**
     * @param  array<string, mixed>  $shortcut
     */
    public static function userCanSeeShortcut(User $user, array $shortcut): bool
    {
        if (isset($shortcut['visible']) && is_callable($shortcut['visible'])) {
            return ($shortcut['visible'])($user);
        }

        if (! empty($shortcut['roles'])) {
            return $user->hasAnyRole($shortcut['roles']);
        }

        if (! empty($shortcut['permission'])) {
            return $user->can($shortcut['permission']);
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private static function crudRoutes(string $prefix, bool $includeDestroy = true): array
    {
        $routes = [
            "{$prefix}.index",
            "{$prefix}.create",
            "{$prefix}.show",
            "{$prefix}.edit",
        ];

        if ($includeDestroy) {
            $routes[] = "{$prefix}.destroy";
        }

        return $routes;
    }
}
