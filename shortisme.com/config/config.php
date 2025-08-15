<?php
/**
 * Database Configuration untuk Shortisme
 * File ini berada di luar public_html untuk keamanan
 * 
 * Struktur folder:
 * shortisme.com/
 * ├── config/           # Di luar public_html
 * │   └── config.php    # File ini
 * └── public_html/      # Web accessible
 *     ├── index.php
 *     ├── api-optimized.php
 *     └── ...
 */

// Database configuration - menggunakan database yang sama dengan premiumisme.co
define('DB_HOST', 'localhost');
define('DB_NAME', 'premiumisme_db'); // Database yang sama dengan premiumisme.co
define('DB_USER', 'premiumisme_user'); // User database premiumisme.co
define('DB_PASS', 'your_secure_password'); // Ganti dengan password yang aman
define('DB_CHARSET', 'utf8mb4');

// Security settings
define('ALLOWED_ORIGINS', [
    'https://premiumisme.co',
    'https://www.premiumisme.co',
    'https://shortisme.com',
    'https://www.shortisme.com'
]);

// PDO connection function dengan error handling yang aman
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Log error ke file log, bukan ke error_log PHP
        error_log("Database connection failed: " . $e->getMessage(), 3, __DIR__ . '/../logs/db_error.log');
        return false;
    }
}

// Helper function to check if database is available
function isDatabaseAvailable() {
    return getDBConnection() !== false;
}

// Security function untuk validasi origin
function validateOrigin() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!empty($origin) && in_array($origin, ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: " . $origin);
    }
}

// Security function untuk validasi input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Security function untuk validasi URL
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) && 
           !preg_match('/^(javascript|data|file):/i', $url) &&
           !preg_match('/^(localhost|127\.|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', parse_url($url, PHP_URL_HOST));
}

// Security function untuk validasi slug
function isValidSlug($slug) {
    return preg_match('/^[a-zA-Z0-9]{6}$/', $slug);
}

// Rate limiting function (basic)
function checkRateLimit($ip, $action = 'default', $limit = 100, $window = 3600) {
    $cacheFile = __DIR__ . '/../logs/rate_limit_' . md5($ip . $action) . '.log';
    $currentTime = time();
    
    // Read existing data
    $data = [];
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    // Clean old entries
    $data = array_filter($data, function($timestamp) use ($currentTime, $window) {
        return ($currentTime - $timestamp) < $window;
    });
    
    // Check limit
    if (count($data) >= $limit) {
        return false;
    }
    
    // Add current request
    $data[] = $currentTime;
    file_put_contents($cacheFile, json_encode($data));
    
    return true;
}
?>
