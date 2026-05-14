<?php

return [

    'ftp' => [
        'host' => trim((string) env('SANTA_CRUZ_FTP_HOST', '')),
        'port' => (int) env('SANTA_CRUZ_FTP_PORT', 21),
        'username' => trim((string) env('SANTA_CRUZ_FTP_USERNAME', '')),
        'password' => trim((string) env('SANTA_CRUZ_FTP_PASSWORD', '')),
        'path' => trim((string) env('SANTA_CRUZ_FTP_PATH', '/IntegracionLaboratorio/Ida')),
        'processed_subpath' => trim(env('SANTA_CRUZ_FTP_PROCESSED_SUBPATH', 'procesados'), '/'),
        'passive' => filter_var(env('SANTA_CRUZ_FTP_PASSIVE', true), FILTER_VALIDATE_BOOL),
        'timeout' => (int) env('SANTA_CRUZ_FTP_TIMEOUT', 30),
        /** FTPS explícito (AUTH TLS), p. ej. IIS «Policy requires SSL» (respuesta 534 en USER sin TLS). */
        'ssl' => filter_var(env('SANTA_CRUZ_FTP_SSL', false), FILTER_VALIDATE_BOOL),
        'ssl_verify_peer' => filter_var(env('SANTA_CRUZ_FTP_SSL_VERIFY_PEER', true), FILTER_VALIDATE_BOOL),
        /** Si la velocidad cae bajo este umbral (bytes/s) durante low_speed_time s, cURL aborta (evita PASV colgado). 0 = desactivado. */
        'low_speed_limit' => (int) env('SANTA_CRUZ_FTP_LOW_SPEED_LIMIT', 1),
        'low_speed_time' => (int) env('SANTA_CRUZ_FTP_LOW_SPEED_TIME', 120),
    ],

    /** Límite de tiempo PHP (segundos) para scan/import Santa Cruz (muchos XML + FTPS). */
    'scan_max_execution_seconds' => (int) env('SANTA_CRUZ_SCAN_MAX_SECONDS', 900),

    /** XML analizados por petición (listar + descargar + parsear). Menor = menos riesgo de «Request Timeout» en IIS/proxy. */
    'scan_batch_size' => max(1, min(500, (int) env('SANTA_CRUZ_SCAN_BATCH_SIZE', 10))),

    'insurance_id' => env('SANTA_CRUZ_INSURANCE_ID') !== null && env('SANTA_CRUZ_INSURANCE_ID') !== ''
        ? (int) env('SANTA_CRUZ_INSURANCE_ID')
        : null,
];
