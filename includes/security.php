<?php
/**
 * Security Helper Functions
 * Kumpulan fungsi untuk keamanan aplikasi
 */

/**
 * Sanitize output untuk mencegah XSS
 * @param string $data Data yang akan di-sanitize
 * @return string Data yang sudah di-sanitize
 */
function sanitize_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input untuk mencegah XSS
 * @param mixed $input Input yang akan di-sanitize
 * @return mixed Input yang sudah di-sanitize
 */
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validasi URL untuk mencegah malicious URLs
 * @param string $url URL yang akan divalidasi
 * @return bool True jika URL valid
 */
function is_safe_url($url) {
    // Validasi format URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Block dangerous protocols
    $parsed = parse_url($url);
    $dangerous_protocols = ['javascript:', 'data:', 'file:', 'vbscript:'];
    
    if (isset($parsed['scheme'])) {
        $scheme = strtolower($parsed['scheme']) . ':';
        if (in_array($scheme, $dangerous_protocols)) {
            return false;
        }
    }
    
    // Block localhost dan private IPs (opsional)
    if (isset($parsed['host'])) {
        $host = $parsed['host'];
        if ($host === 'localhost' || 
            strpos($host, '127.') === 0 || 
            strpos($host, '192.168.') === 0 ||
            strpos($host, '10.') === 0 ||
            preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Validasi slug untuk shortlink
 * @param string $slug Slug yang akan divalidasi
 * @return bool True jika slug valid
 */
function is_valid_slug($slug) {
    return preg_match('/^[a-zA-Z0-9]{6}$/', $slug);
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token yang akan diverifikasi
 * @return bool True jika token valid
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting function
 * @param string $ip IP address
 * @param string $action Action yang dilakukan
 * @param int $limit Batas maksimal
 * @param int $window Window time dalam detik
 * @return bool True jika dalam batas
 */
function check_rate_limit($ip, $action = 'default', $limit = 100, $window = 3600) {
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

/**
 * Log security events
 * @param string $event Event yang terjadi
 * @param array $data Data tambahan
 */
function log_security_event($event, $data = []) {
    $logFile = __DIR__ . '/../logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logEntry = [
        'timestamp' => $timestamp,
        'ip' => $ip,
        'user_agent' => $userAgent,
        'event' => $event,
        'data' => $data
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Set security headers
 */
function set_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com; img-src \'self\' data: https:;');
}
?>
