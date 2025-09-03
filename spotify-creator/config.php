<?php

function env(string $key, $default = null) {
    $val = getenv($key);
    return $val === false ? $default : $val;
}

function load_config(): array {
    // Hardcoded config values instead of relying on .env file
    $config = [
        'SQLITE_PATH' => __DIR__ . '/../spo_creator.db',
        // CLI mode - no more Flask
        'CLI_PATH' => __DIR__ . '/py/cli_create.py',
        'SPOTIFY_DOMAIN' => 'motionisme.com',  // Hardcoded default
        'SPOTIFY_PASSWORD' => 'Premium@123',   // Hardcoded default
        // Allow disabling PHP-side rate limit (useful for localhost/dev)
        'DISABLE_RATE_LIMIT' => false,  // Hardcoded to false (rate limit enabled)
        // Control whether to show verbose debug info in UI (default: hidden)
        'SHOW_DEBUG' => true,  // Hardcoded to true for debugging
    ];
    return $config;
}


