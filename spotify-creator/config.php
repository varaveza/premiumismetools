<?php

function env(string $key, $default = null) {
    $val = getenv($key);
    return $val === false ? $default : $val;
}

function load_config(): array {
    // Load .env file
    if (file_exists(__DIR__ . '/.env')) {
        $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
    
    // Simple env loader; for production you can switch to vlucas/phpdotenv
    $config = [
        'SQLITE_PATH' => __DIR__ . '/../spo_creator.db',
        // CLI mode - no more Flask
        'CLI_PATH' => __DIR__ . '/py/cli_create.py',
        'SPOTIFY_DOMAIN' => env('DOMAIN', ''),
        'SPOTIFY_PASSWORD' => env('PASSWORD', ''),
        // Allow disabling PHP-side rate limit (useful for localhost/dev)
        'DISABLE_RATE_LIMIT' => strtolower((string) env('DISABLE_RATE_LIMIT', 'false')) === 'true',
        // Control whether to show verbose debug info in UI (default: hidden)
        'SHOW_DEBUG' => strtolower((string) env('SHOW_DEBUG', 'false')) === 'true',
    ];
    return $config;
}


