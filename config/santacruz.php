<?php

return [

    'ftp' => [
        'host' => env('SANTA_CRUZ_FTP_HOST'),
        'port' => (int) env('SANTA_CRUZ_FTP_PORT', 21),
        'username' => env('SANTA_CRUZ_FTP_USERNAME'),
        'password' => env('SANTA_CRUZ_FTP_PASSWORD'),
        'path' => env('SANTA_CRUZ_FTP_PATH', '/IntegracionLaboratorio/Ida'),
        'processed_subpath' => trim(env('SANTA_CRUZ_FTP_PROCESSED_SUBPATH', 'procesados'), '/'),
        'passive' => filter_var(env('SANTA_CRUZ_FTP_PASSIVE', true), FILTER_VALIDATE_BOOL),
        'timeout' => (int) env('SANTA_CRUZ_FTP_TIMEOUT', 30),
    ],

    'insurance_id' => env('SANTA_CRUZ_INSURANCE_ID') !== null && env('SANTA_CRUZ_INSURANCE_ID') !== ''
        ? (int) env('SANTA_CRUZ_INSURANCE_ID')
        : null,
];
