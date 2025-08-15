<?php
// Set proper headers for redirect
header('Content-Type: text/html; charset=utf-8');

$dbFile = __DIR__ . "/shortlinks.json";
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE html><html><head><title>404 - Shortlink Not Found</title></head><body><h1>Shortlink tidak ditemukan</h1><p>Slug tidak valid atau kosong.</p></body></html>";
    exit;
}

if (file_exists($dbFile)) {
    $links = json_decode(file_get_contents($dbFile), true);
} else {
    $links = [];
}

$foundLink = null;
foreach ($links as $link) {
    if ($link['slug'] === $slug) {
        $foundLink = $link;
        break;
    }
}

if ($foundLink) {
    // Increment click count
    $foundLink['clicks']++;
    foreach ($links as &$link) {
        if ($link['slug'] === $slug) {
            $link['clicks'] = $foundLink['clicks'];
            break;
        }
    }
    
    // Save updated data
    file_put_contents($dbFile, json_encode($links, JSON_PRETTY_PRINT));
    
    // Redirect to original URL
    header("Location: " . $foundLink['originalUrl']);
    exit;
} else {
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE html><html><head><title>404 - Shortlink Not Found</title></head><body><h1>Shortlink tidak ditemukan</h1><p>Link dengan slug '{$slug}' tidak ditemukan.</p></body></html>";
    exit;
}
?>
