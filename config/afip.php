<?php

return [
    'cuit' => env('AFIP_CUIT'),
    'cert_path' => env('AFIP_CERT_PATH', storage_path('app/afip/cert.pem')),
    'key_path' => env('AFIP_KEY_PATH', storage_path('app/afip/key.pem')),
    'production' => env('AFIP_PRODUCTION', false),

    'emisor' => [
        'razon_social' => env('AFIP_RAZON_SOCIAL', 'IPAC Laboratorio de Aguas y Alimentos'),
        'domicilio' => env('AFIP_DOMICILIO', 'Neuquén, Neuquén'),
        'condicion_iva' => env('AFIP_CONDICION_IVA', 'IVA Responsable Inscripto'),
        'inicio_actividades' => env('AFIP_INICIO_ACTIVIDADES', '01/01/2020'),
        'ingresos_brutos' => env('AFIP_IIBB', ''),
    ],
];
