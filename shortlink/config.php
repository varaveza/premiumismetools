<?php
// Konfigurasi domain untuk shortlink
define('SHORTLINK_DOMAIN', 'https://shortisme.com');
define('TOOLS_DOMAIN', 'https://premiumisme.co/tools');

// API endpoint untuk cross-domain (menggunakan database MySQL)
define('API_ENDPOINT', SHORTLINK_DOMAIN . '/api-optimized.php');

// Database configuration (untuk referensi)
define('DB_HOST', 'localhost');
define('DB_NAME', 'premiumisme_db'); // Database yang sama dengan premiumisme.co
define('DB_USER', 'premiumisme_user');
define('DB_PASS', 'your_secure_password'); // Ganti dengan password yang aman
?>
