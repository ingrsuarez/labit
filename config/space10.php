<?php

return [
    'enabled' => filter_var(env('SPACE10_ENABLED', false), FILTER_VALIDATE_BOOL),
    'api_url' => rtrim((string) env('SPACE10_API_URL', ''), '/'),
    'api_token' => trim((string) env('SPACE10_API_TOKEN', '')),
    'timeout' => (int) env('SPACE10_TIMEOUT', 30),
];
