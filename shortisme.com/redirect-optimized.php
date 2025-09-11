<?php
// Set proper headers for redirect
header('Content-Type: text/html; charset=utf-8');

// Include config from outside public_html
require_once '../config/config.php';

// Rate limiting untuk redirect
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit($clientIP, 'redirect', 100, 60)) {
    http_response_code(429);
    echo "<!DOCTYPE html><html><head><title>429 - Too Many Requests</title></head><body><h1>Too Many Requests</h1><p>Please try again later.</p></body></html>";
    exit;
}

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE html><html><head><title>404 - Shortlink Not Found</title></head><body><h1>Shortlink tidak ditemukan</h1><p>Slug tidak valid atau kosong.</p></body></html>";
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    header("HTTP/1.0 500 Internal Server Error");
    echo "<!DOCTYPE html><html><head><title>500 - Server Error</title></head><body><h1>Server Error</h1><p>Database connection failed.</p></body></html>";
    exit;
}

try {
    // Get link data with prepared statement for security
    $stmt = $pdo->prepare("
        SELECT id, slug, original_url, clicks 
        FROM shortlinks 
        WHERE slug = ?
    ");
    $stmt->execute([$slug]);
    $link = $stmt->fetch();
    
    if ($link) {
        // Increment click count using atomic update
        $stmt = $pdo->prepare("
            UPDATE shortlinks 
            SET clicks = clicks + 1, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$link['id']]);
        
        // Optional: Log analytics data
        logAnalytics($pdo, $link['id']);
        
        // Redirect to original URL
        header("Location: " . $link['original_url']);
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "<!DOCTYPE html><html><head><title>404 - Shortlink Not Found</title></head><body><h1>Shortlink tidak ditemukan</h1><p>Link dengan slug '" . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . "' tidak ditemukan.</p></body></html>";
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Database error in redirect: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    echo "<!DOCTYPE html><html><head><title>500 - Server Error</title></head><body><h1>Server Error</h1><p>Database error occurred.</p></body></html>";
    exit;
}

function logAnalytics($pdo, $shortlinkId) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO link_analytics (shortlink_id, ip_address, user_agent, referer) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $shortlinkId,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_REFERER'] ?? ''
        ]);
    } catch (PDOException $e) {
        // Log error but don't break the redirect
        error_log("Analytics logging failed: " . $e->getMessage());
    }
}
?>
