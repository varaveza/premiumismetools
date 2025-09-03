<?php

function env(string $key, $default = null) {
    $val = getenv($key);
    return $val === false ? $default : $val;
}

function load_config(): array {
    // Simple env loader; for production you can switch to vlucas/phpdotenv
    $config = [
        'SQLITE_PATH' => __DIR__ . '/../spo_creator.db',
        'FLASK_API' => env('FLASK_API', 'http://127.0.0.1:5111/api/create'),
        // Accept either FLASK_BACKEND_API_KEY or BACKEND_API_KEY
        'FLASK_BACKEND_API_KEY' => env('FLASK_BACKEND_API_KEY', env('BACKEND_API_KEY', '')),
        'SPOTIFY_DOMAIN' => env('SPOTIFY_DOMAIN', ''),
        'SPOTIFY_PASSWORD' => env('SPOTIFY_PASSWORD', ''),
    ];
    return $config;
}


