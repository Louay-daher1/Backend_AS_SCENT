<?php

$frontendOrigins = array_values(array_unique(array_filter(array_merge(
    [
        'http://localhost:8080',
        'http://127.0.0.1:8080',
    ],
    array_map('trim', explode(',', (string) env('FRONTEND_URL', ''))),
))));

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $frontendOrigins,

    'allowed_origins_patterns' => [
        '#^http://192\.168\.\d{1,3}\.\d{1,3}:8080$#',
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
