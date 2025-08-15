<?php
// Konfigurasi domain untuk shortlink
define('SHORTLINK_DOMAIN', 'https://shortisme.com');
define('TOOLS_DOMAIN', 'https://premiumisme.co/tools');

// Database file path
define('DB_FILE', __DIR__ . '/shortlinks.json');

// API endpoint untuk cross-domain
define('API_ENDPOINT', SHORTLINK_DOMAIN . '/api.php');
?>
