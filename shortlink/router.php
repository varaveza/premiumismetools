<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '/shortlink/';
if (strpos($path, $base) !== 0) {
    http_response_code(404);
    exit('Not Found');
}
$rest = substr($path, strlen($base));
$parts = explode('/', trim($rest, '/'));
$slug = $parts[0] ?? '';
if (!preg_match('/^[A-Za-z0-9]{6}$/', $slug)) {
    http_response_code(404);
    exit('Not Found');
}
$_GET['slug'] = $slug;
if (isset($parts[1]) && $parts[1] === 'stats') {
    require __DIR__ . '/stats.php';
} else {
    require __DIR__ . '/redirect.php';
}
