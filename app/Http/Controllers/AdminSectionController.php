<?php

namespace App\Http\Controllers;

class AdminSectionController extends Controller
{
    public function personal()
    {
        return redirect()->route('rrhh.index')->withFragment('personal');
    }

    public function ausencias()
    {
        return redirect()->route('rrhh.index')->withFragment('ausencias');
    }

    public function liquidaciones()
    {
        return redirect()->route('rrhh.index')->withFragment('liquidaciones');
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
                [
                    'name' => 'API Keys',
                    'description' => 'Claves de la API pública para integraciones (LISCOM, etc.)',
                    'route' => route('api-clients.index'),
                    'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
                ],
            ],
        ];

        return view('admin.section', compact('section'));
    }
}
