<?php

function env(string $key, $default = null) {
    $val = getenv($key);
    return $val === false ? $default : $val;
}

function load_config(): array {
    // Hardcoded config values instead of relying on .env file
    $config = [
        'SQLITE_PATH' => __DIR__ . '/../spo_creator.db',
        'SPOTIFY_DOMAIN' => 'motionisme.com',  // Hardcoded default
        'SPOTIFY_PASSWORD' => 'Premium@123',   // Hardcoded default
        // Allow disabling PHP-side rate limit (useful for localhost/dev)
        'DISABLE_RATE_LIMIT' => false,  // Rate limit enabled - 2 users per IP per day
        // Allow disabling daily limit (useful for localhost/dev)
        'DISABLE_DAILY_LIMIT' => false,  // Daily limit enabled - 50 accounts per day
        // Control whether to show verbose debug info in UI (default: hidden for security)
        'SHOW_DEBUG' => false,  // Hardcoded to false for production security
        // API configuration for index.php
        'API_ENDPOINT' => 'http://localhost:5112/api/create',  // API endpoint URL
        'API_KEY' => '',  // Optional API key for authentication
    ];
    return $config;
}


