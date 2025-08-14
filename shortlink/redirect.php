<?php
$dbFile = __DIR__ . "/shortlinks.json";
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "Shortlink tidak ditemukan";
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
    $foundLink['clicks']++;
    foreach ($links as &$link) {
        if ($link['slug'] === $slug) {
            $link['clicks'] = $foundLink['clicks'];
            break;
        }
    }
    file_put_contents($dbFile, json_encode($links, JSON_PRETTY_PRINT));
    header("Location: " . $foundLink['originalUrl']);
    exit;
} else {
    header("HTTP/1.0 404 Not Found");
    echo "Shortlink tidak ditemukan";
    exit;
}
?>
