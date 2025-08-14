<?php
// Database connection (gunakan file JSON sebagai database sederhana)
$dbFile = 'shortlinks.json';

// Get slug from URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "Shortlink tidak ditemukan";
    exit;
}

// Load database
if (file_exists($dbFile)) {
    $links = json_decode(file_get_contents($dbFile), true);
} else {
    $links = [];
}

// Find the link
$foundLink = null;
foreach ($links as $link) {
    if ($link['slug'] === $slug) {
        $foundLink = $link;
        break;
    }
}

if ($foundLink) {
    // Update click count
    $foundLink['clicks']++;
    
    // Update database
    foreach ($links as &$link) {
        if ($link['slug'] === $slug) {
            $link['clicks'] = $foundLink['clicks'];
            break;
        }
    }
    file_put_contents($dbFile, json_encode($links, JSON_PRETTY_PRINT));
    
    // Redirect to original URL
    header("Location: " . $foundLink['originalUrl']);
    exit;
} else {
    header("HTTP/1.0 404 Not Found");
    echo "Shortlink tidak ditemukan";
    exit;
}
?>
