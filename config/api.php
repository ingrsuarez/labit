<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Retención de logs de la API (días)
    |--------------------------------------------------------------------------
    | Controla cuántos días se conservan los registros de ResultBatch y
    | ResultIngestion antes de ser eliminados por el comando api:cleanup.
    | Valor por defecto: 90 días. Configurable vía API_LOG_RETENTION_DAYS.
    */
    'log_retention_days' => (int) env('API_LOG_RETENTION_DAYS', 90),
];
