<?php

namespace App\Http\Controllers;

class LabSectionController extends Controller
{
    public function clinico()
    {
        $section = [
            'title' => 'Laboratorio Clínico',
            'description' => 'Gestión de protocolos, pacientes y obras sociales',
            'items' => [
                [
                    'name' => 'Protocolos',
                    'description' => 'Listado de protocolos clínicos',
                    'route' => route('lab.admissions.index'),
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                ],
                [
                    'name' => 'Nuevo Protocolo',
                    'description' => 'Crear protocolo de análisis',
                    'route' => route('lab.admissions.create'),
                    'icon' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
                [
                    'name' => 'Nuevo Paciente',
                    'description' => 'Registrar un nuevo paciente',
                    'route' => route('patient.index'),
                    'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                ],
                [
                    'name' => 'Obras Sociales',
                    'description' => 'Gestión de coberturas',
                    'route' => route('insurance.index'),
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                ],
                [
                    'name' => 'Reportes',
                    'description' => 'Informes y estadísticas',
                    'route' => route('lab.reports.monthly'),
                    'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                ],
            ],
        ];

        return view('lab.section', compact('section'));
    }

    public function muestras()
    {
        $section = [
            'title' => 'Laboratorio de Aguas y Alimentos',
            'description' => 'Gestión de muestras y presupuestos',
            'items' => [
                [
                    'name' => 'Protocolos',
                    'description' => 'Listado de protocolos de muestras',
                    'route' => route('sample.index'),
                    'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
                ],
                [
                    'name' => 'Nueva Muestra',
                    'description' => 'Registrar nueva muestra',
                    'route' => route('sample.create'),
                    'icon' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
                [
                    'name' => 'Presupuestos',
                    'description' => 'Crear y gestionar presupuestos',
                    'route' => route('quotes.index'),
                    'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                ],
            ],
        ];

        return view('lab.section', compact('section'));
    }

    public function configuracion()
    {
        $section = [
            'title' => 'Configuración',
            'description' => 'Determinaciones, materiales, servicios y nomencladores',
            'items' => [
                [
                    'name' => 'Determinaciones',
                    'description' => 'Gestión de análisis disponibles',
                    'route' => route('tests.index'),
                    'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
                ],
                [
                    'name' => 'Valores de Referencia',
                    'description' => 'Categorías de valores de referencia',
                    'route' => route('reference-categories.index'),
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                ],
                [
                    'name' => 'Materiales',
                    'description' => 'Gestión de materiales de laboratorio',
                    'route' => route('materials.index'),
                    'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                ],
                [
                    'name' => 'Servicios',
                    'description' => 'Servicios adicionales para presupuestos',
                    'route' => route('services.index'),
                    'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                ],
                [
                    'name' => 'Nomencladores',
                    'description' => 'Precios por obra social',
                    'route' => route('nomenclator.index'),
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
            ],
        ];

        return view('lab.section', compact('section'));
    }
}
