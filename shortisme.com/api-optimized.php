<?php
header('Content-Type: application/json');

// Include config from outside public_html
require_once '../config/config.php';

// Security: Validate origin
validateOrigin();

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit($clientIP, 'api', 1000, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createShortlink();
        break;
        
    case 'delete':
        deleteShortlink();
        break;
        
    case 'get':
        getShortlink();
        break;
        
    case 'stats':
        getStats();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

function createShortlink() {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        return;
    }
    
    $data = json_decode($_POST['data'], true);
    
    try {
        // Check if slug already exists
        $stmt = $pdo->prepare("SELECT id FROM shortlinks WHERE slug = ?");
        $stmt->execute([$data['slug']]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Slug already exists']);
            return;
        }
        
        // Insert new link
        $stmt = $pdo->prepare("
            INSERT INTO shortlinks (slug, original_url, clicks, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['slug'],
            $data['originalUrl'],
            0
        ]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function deleteShortlink() {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        return;
    }
    
    $id = $_POST['id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("DELETE FROM shortlinks WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function getShortlink() {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        return;
    }
    
    $slug = $_GET['slug'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, slug, original_url, clicks, created_at 
            FROM shortlinks 
            WHERE slug = ?
        ");
        $stmt->execute([$slug]);
        $link = $stmt->fetch();
        
        if ($link) {
            echo json_encode(['success' => true, 'data' => $link]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Link not found']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function getStats() {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        return;
    }
    
    try {
        // Get total links
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM shortlinks");
        $total = $stmt->fetch()['total'];
        
        // Get total clicks
        $stmt = $pdo->query("SELECT SUM(clicks) as total_clicks FROM shortlinks");
        $totalClicks = $stmt->fetch()['total_clicks'] ?? 0;
        
        // Get top 10 most clicked links
        $stmt = $pdo->query("
            SELECT slug, original_url, clicks, created_at 
            FROM shortlinks 
            ORDER BY clicks DESC 
            LIMIT 10
        ");
        $topLinks = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_links' => $total,
                'total_clicks' => $totalClicks,
                'top_links' => $topLinks
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}
?>
