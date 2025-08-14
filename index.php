<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

// If path is empty, redirect to shortlink page
if (empty($path)) {
    header('Location: /shortlink/', true, 301);
    exit('Redirecting to shortlink...');
}

// Check if it's a 6-character alphanumeric shortlink
if (preg_match('/^[A-Za-z0-9]{6}$/', $path)) {
    $_GET['slug'] = $path;
    require __DIR__ . '/shortlink/redirect.php';
    exit;
}

// Check if it's a shortlink with /stats
if (preg_match('/^([A-Za-z0-9]{6})\/stats$/', $path, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/shortlink/stats.php';
    exit;
}

// If it's /shortlink/, serve the shortlink page
if ($path === 'shortlink') {
    require __DIR__ . '/shortlink/index.php';
    exit;
}

// Default: redirect to shortlink page
header('Location: /shortlink/', true, 301);
exit('Redirecting to shortlink...');
?>
