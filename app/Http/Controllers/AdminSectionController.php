<?php

namespace App\Http\Controllers;

class AdminSectionController extends Controller
{
    public function personal()
    {
        $section = [
            'title' => 'Personal',
            'description' => 'Gestión de empleados, puestos, documentos y organización',
            'items' => [
                [
                    'name' => 'Empleados',
                    'description' => 'Listado y gestión de empleados',
                    'route' => route('employee.show'),
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                ],
                [
                    'name' => 'Puestos',
                    'description' => 'Gestión de puestos de trabajo',
                    'route' => route('job.list'),
                    'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                ],
                [
                    'name' => 'Categorías',
                    'description' => 'Categorías de empleados',
                    'route' => route('category.index'),
                    'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                ],
                [
                    'name' => 'Documentos',
                    'description' => 'Gestión documental',
                    'route' => route('documents.index'),
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                ],
                [
                    'name' => 'Circulares',
                    'description' => 'Comunicaciones internas',
                    'route' => route('circular.index'),
                    'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
                ],
                [
                    'name' => 'Organigrama',
                    'description' => 'Estructura organizacional',
                    'route' => route('manage.chart'),
                    'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
                ],
                [
                    'name' => 'No Conformidades',
                    'description' => 'Registro de no conformidades',
                    'route' => route('non-conformity.index'),
                    'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                ],
            ],
        ];

        return view('admin.section', compact('section'));
    }

    public function ausencias()
    {
        $section = [
            'title' => 'Ausencias',
            'description' => 'Vacaciones, licencias y calendario de ausencias',
            'items' => [
                [
                    'name' => 'Vacaciones',
                    'description' => 'Solicitar y gestionar vacaciones',
                    'route' => route('vacation.index'),
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                ],
                [
                    'name' => 'Aprobaciones',
                    'description' => 'Aprobar o rechazar solicitudes',
                    'route' => route('vacation.approval'),
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
                [
                    'name' => 'Calendario',
                    'description' => 'Vista de calendario de ausencias',
                    'route' => route('vacation.calendar'),
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                ],
                [
                    'name' => 'Feriados',
                    'description' => 'Gestión de feriados nacionales',
                    'route' => route('vacation.holidays'),
                    'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                ],
                [
                    'name' => 'Resumen Novedades',
                    'description' => 'Resumen general de novedades',
                    'route' => route('leave.resume'),
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                ],
                [
                    'name' => 'Gestionar Licencias',
                    'description' => 'Crear y editar licencias',
                    'route' => route('leave.index'),
                    'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                ],
            ],
        ];

        return view('admin.section', compact('section'));
    }

    public function liquidaciones()
    {
        $section = [
            'title' => 'Liquidaciones',
            'description' => 'Recibos de sueldo, historial y conceptos salariales',
            'items' => [
                [
                    'name' => 'Generar Recibos',
                    'description' => 'Crear recibos de sueldo',
                    'route' => route('payroll.index'),
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
                [
                    'name' => 'Historial',
                    'description' => 'Recibos cerrados y pagados',
                    'route' => route('payroll.closed'),
                    'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
                ],
                [
                    'name' => 'Conceptos Salariales',
                    'description' => 'Configurar conceptos de sueldo',
                    'route' => route('salary.index'),
                    'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                ],
            ],
        ];

        return view('admin.section', compact('section'));
    }

    public function configuracion()
    {
        $section = [
            'title' => 'Configuración',
            'description' => 'Usuarios, roles y permisos del sistema',
            'items' => [
                [
                    'name' => 'Usuarios',
                    'description' => 'Gestión de usuarios del sistema',
                    'route' => route('user.index'),
                    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                ],
                [
                    'name' => 'Roles y Permisos',
                    'description' => 'Configurar accesos al sistema',
                    'route' => route('role.new'),
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                ],
            ],
        ];

        return view('admin.section', compact('section'));
    }
}
