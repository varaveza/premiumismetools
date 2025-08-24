<?php
/**
 * Database Setup Script untuk Shortlink System
 * Menggunakan database yang sama dengan premiumisme.co
 * 
 * Cara penggunaan:
 * 1. Update config.php dengan kredensial database yang benar
 * 2. Jalankan: php setup-database.php
 * 3. Atau akses via browser: https://shortisme.com/setup-database.php
 */

// Include config from outside public_html
require_once '../config/config.php';

// Security: Only allow setup in development or with proper authentication
if (!isset($_GET['setup']) && !defined('ALLOW_SETUP')) {
    die('Setup disabled. Add ?setup=1 to URL or define ALLOW_SETUP constant.');
}

function setupDatabase() {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Create shortlinks table
        $sql = "CREATE TABLE IF NOT EXISTS shortlinks (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            slug VARCHAR(10) UNIQUE NOT NULL,
            original_url TEXT NOT NULL,
            clicks INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes for optimal performance
            INDEX idx_slug (slug),
            INDEX idx_created_at (created_at),
            INDEX idx_clicks (clicks)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        // Create analytics table
        $sql = "CREATE TABLE IF NOT EXISTS link_analytics (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            shortlink_id BIGINT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            referer TEXT,
            clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (shortlink_id) REFERENCES shortlinks(id) ON DELETE CASCADE,
            INDEX idx_shortlink_id (shortlink_id),
            INDEX idx_clicked_at (clicked_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        // Insert sample data for testing
        $stmt = $pdo->prepare("INSERT IGNORE INTO shortlinks (slug, original_url, clicks) VALUES (?, ?, ?)");
        $stmt->execute(['abc123', 'https://example.com/very-long-url-here', 0]);
        $stmt->execute(['def456', 'https://google.com/search?q=test', 0]);
        
        return ['success' => true, 'message' => 'Database setup completed successfully'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database setup failed: ' . $e->getMessage()];
    }
}

function testDatabase() {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Test basic operations
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM shortlinks");
        $count = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM link_analytics");
        $analyticsCount = $stmt->fetch()['count'];
        
        return [
            'success' => true, 
            'message' => "Database test successful",
            'data' => [
                'shortlinks_count' => $count,
                'analytics_count' => $analyticsCount
            ]
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database test failed: ' . $e->getMessage()];
    }
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'setup':
            echo json_encode(setupDatabase());
            break;
        case 'test':
            echo json_encode(testDatabase());
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

// HTML Interface
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Shortisme</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../favicon.png">
    <link rel="shortcut icon" href="../favicon.png">
    <link rel="apple-touch-icon" href="../favicon.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #1a1a1a;
            color: #fff;
        }
        .container {
            background: #2a2a2a;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid #333;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background: #28a745;
            color: white;
        }
        .error {
            background: #dc3545;
            color: white;
        }
        .info {
            background: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Setup - Shortisme</h1>
        <p>Setup database untuk sistem shortlink menggunakan database yang sama dengan premiumisme.co</p>
        
        <div id="result"></div>
        
        <div>
            <button class="btn" onclick="setupDatabase()">🚀 Setup Database</button>
            <button class="btn" onclick="testDatabase()">🧪 Test Database</button>
            <button class="btn btn-danger" onclick="clearResult()">🗑️ Clear</button>
        </div>
        
        <div style="margin-top: 30px;">
            <h3>📋 Database Configuration:</h3>
            <ul>
                <li><strong>Host:</strong> <?= DB_HOST ?></li>
                <li><strong>Database:</strong> <?= DB_NAME ?></li>
                <li><strong>User:</strong> <?= DB_USER ?></li>
                <li><strong>Charset:</strong> <?= DB_CHARSET ?></li>
            </ul>
        </div>
    </div>

    <script>
        function showResult(data) {
            const resultDiv = document.getElementById('result');
            const className = data.success ? 'success' : 'error';
            const message = data.success ? data.message : data.error;
            
            resultDiv.innerHTML = `
                <div class="result ${className}">
                    <strong>${data.success ? '✅ Success' : '❌ Error'}:</strong> ${message}
                    ${data.data ? '<br><pre>' + JSON.stringify(data.data, null, 2) + '</pre>' : ''}
                </div>
            `;
        }
        
        function setupDatabase() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=setup'
            })
            .then(response => response.json())
            .then(data => showResult(data))
            .catch(error => showResult({success: false, error: 'Request failed: ' + error.message}));
        }
        
        function testDatabase() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=test'
            })
            .then(response => response.json())
            .then(data => showResult(data))
            .catch(error => showResult({success: false, error: 'Request failed: ' + error.message}));
        }
        
        function clearResult() {
            document.getElementById('result').innerHTML = '';
        }
    </script>
</body>
</html>
