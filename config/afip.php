<?php

return [
    'cuit' => env('AFIP_CUIT'),
    'cert_path' => env('AFIP_CERT_PATH', storage_path('app/afip/cert.pem')),
    'key_path' => env('AFIP_KEY_PATH', storage_path('app/afip/key.pem')),
    'production' => env('AFIP_PRODUCTION', false),
];
