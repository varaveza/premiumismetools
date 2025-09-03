<?php

function env(string $key, $default = null) {
    $val = getenv($key);
    return $val === false ? $default : $val;
}

function load_config(): array {
    // Simple env loader; for production you can switch to vlucas/phpdotenv
    $config = [
        'SQLITE_PATH' => __DIR__ . '/../spo_creator.db',
        // CLI mode - no more Flask
        'CLI_PATH' => __DIR__ . '/py/cli_create.py',
        'SPOTIFY_DOMAIN' => env('SPOTIFY_DOMAIN', ''),
        'SPOTIFY_PASSWORD' => env('SPOTIFY_PASSWORD', ''),
        // Allow disabling PHP-side rate limit (useful for localhost/dev)
        'DISABLE_RATE_LIMIT' => strtolower((string) env('DISABLE_RATE_LIMIT', 'false')) === 'true',
    ];
    return $config;
}


