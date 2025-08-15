<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://premiumisme.co');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dbFile = 'shortlinks.json';

// Load existing data
if (file_exists($dbFile)) {
    $links = json_decode(file_get_contents($dbFile), true);
} else {
    $links = [];
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $data = json_decode($_POST['data'], true);
        
        // Check if slug already exists
        $slugExists = false;
        foreach ($links as $link) {
            if ($link['slug'] === $data['slug']) {
                $slugExists = true;
                break;
            }
        }
        
        if ($slugExists) {
            echo json_encode(['success' => false, 'error' => 'Slug already exists']);
            exit;
        }
        
        // Add new link
        $links[] = $data;
        file_put_contents($dbFile, json_encode($links, JSON_PRETTY_PRINT));
        
        echo json_encode(['success' => true]);
        break;
        
    case 'delete':
        $id = $_POST['id'] ?? '';
        
        $links = array_filter($links, function($link) use ($id) {
            return $link['id'] !== $id;
        });
        
        file_put_contents($dbFile, json_encode(array_values($links), JSON_PRETTY_PRINT));
        
        echo json_encode(['success' => true]);
        break;
        
    case 'get':
        $slug = $_GET['slug'] ?? '';
        
        foreach ($links as $link) {
            if ($link['slug'] === $slug) {
                echo json_encode(['success' => true, 'data' => $link]);
                exit;
            }
        }
        
        echo json_encode(['success' => false, 'error' => 'Link not found']);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>
