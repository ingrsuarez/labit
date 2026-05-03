<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard ejecutivo financiero (v1.66.0)
    |--------------------------------------------------------------------------
    |
    | Configuración del panel financiero principal (`/dashboard`).
    | Cada widget tiene una paleta de colores propia para diferenciar conceptos
    | sin cargar juicio de valor (ej: rojo en egresos NO significa "alerta").
    |
    */

    'financial' => [
        'palettes' => [
            'ventas' => [
                'icon_bg' => 'bg-emerald-100',
                'icon_text' => 'text-emerald-600',
                'bar' => 'bg-emerald-400',
                'bar_current' => 'bg-emerald-600',
            ],
            'compras' => [
                'icon_bg' => 'bg-amber-100',
                'icon_text' => 'text-amber-600',
                'bar' => 'bg-amber-400',
                'bar_current' => 'bg-amber-600',
            ],
            'ingresos' => [
                'icon_bg' => 'bg-sky-100',
                'icon_text' => 'text-sky-600',
                'bar' => 'bg-sky-400',
                'bar_current' => 'bg-sky-600',
            ],
            'egresos' => [
                'icon_bg' => 'bg-rose-100',
                'icon_text' => 'text-rose-600',
                'bar' => 'bg-rose-400',
                'bar_current' => 'bg-rose-600',
            ],
        ],
    ],
];
