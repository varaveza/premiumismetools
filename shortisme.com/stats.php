<?php
// Include config from outside public_html
require_once '../config/config.php';

// Rate limiting untuk stats
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit($clientIP, 'stats', 50, 60)) {
    http_response_code(429);
    echo "<!DOCTYPE html><html><head><title>429 - Too Many Requests</title></head><body><h1>Too Many Requests</h1><p>Please try again later.</p></body></html>";
    exit;
}

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE html><html><head><title>404 - Stats Not Found</title></head><body><h1>Stats tidak ditemukan</h1><p>Slug tidak valid atau kosong.</p></body></html>";
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    header("HTTP/1.0 500 Internal Server Error");
    echo "<!DOCTYPE html><html><head><title>500 - Server Error</title></head><body><h1>Server Error</h1><p>Database connection failed.</p></body></html>";
    exit;
}

try {
    // Get link data
    $stmt = $pdo->prepare("
        SELECT id, slug, original_url, clicks, created_at 
        FROM shortlinks 
        WHERE slug = ?
    ");
    $stmt->execute([$slug]);
    $link = $stmt->fetch();
    
    if (!$link) {
        header("HTTP/1.0 404 Not Found");
        echo "<!DOCTYPE html><html><head><title>404 - Link Not Found</title></head><body><h1>Link tidak ditemukan</h1><p>Link dengan slug '{$slug}' tidak ditemukan.</p></body></html>";
        exit;
    }
    
    // Get analytics data (last 30 days)
    $stmt = $pdo->prepare("
        SELECT DATE(clicked_at) as date, COUNT(*) as clicks
        FROM link_analytics 
        WHERE shortlink_id = ? 
        AND clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(clicked_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$link['id']]);
    $analytics = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in stats: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    echo "<!DOCTYPE html><html><head><title>500 - Server Error</title></head><body><h1>Server Error</h1><p>Database error occurred.</p></body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stats - <?= htmlspecialchars($slug) ?> | Shortisme</title>
    <link rel="stylesheet" href="premiumisme.co/assets/css/style.css">
    <style>
        /* Custom styles for stats page */
        .stats-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .stats-header h1 {
            color: var(--accent);
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .shortlink-info {
            background: var(--accent-glass-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            word-break: break-all;
            border: 1px solid var(--glass-border);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: var(--accent-glass-bg);
            border-radius: 8px;
            border: 1px solid var(--glass-border);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--accent);
        }
        
        .stat-label {
            color: var(--text-light);
            margin-top: 5px;
            opacity: 0.8;
        }
        
        .chart-container {
            background: var(--accent-glass-bg);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid var(--glass-border);
        }
        
        .chart-title {
            text-align: center;
            margin-bottom: 20px;
            color: var(--text-light);
            font-weight: bold;
        }
        
        .chart-bar {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .chart-label {
            width: 80px;
            font-size: 0.9em;
            color: var(--text-light);
        }
        
        .chart-bar-bg {
            flex: 1;
            height: 20px;
            background: var(--darker-peri);
            border-radius: 10px;
            margin: 0 10px;
            overflow: hidden;
            border: 1px solid var(--glass-border);
        }
        
        .chart-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), var(--light-peri));
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .chart-value {
            width: 50px;
            text-align: right;
            font-size: 0.9em;
            font-weight: bold;
            color: var(--accent);
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: var(--light-peri);
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .stats-header h1 {
                font-size: 2em;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-label {
                width: 60px;
                font-size: 0.8em;
            }
            
            .chart-value {
                width: 40px;
                font-size: 0.8em;
            }
        }
    </style>
</head>
<body>
    <!-- Background Animation -->
    <div class="bg-animation">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container-main">
        <div class="content-wrapper">
            <div class="content-section">
                <div class="stats-header">
                    <h1>📊 Statistik Link</h1>
                    <p>Analisis performa shortlink Anda</p>
                </div>

                <div class="shortlink-info">
                    <strong>Shortlink:</strong> https://shortisme.com/<?= htmlspecialchars($slug) ?><br>
                    <strong>URL Asli:</strong> <?= htmlspecialchars($link['original_url']) ?>
                </div>
                        
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($link['clicks']) ?></div>
                        <div class="stat-label">Total Klik</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= date('d/m/Y', strtotime($link['created_at'])) ?></div>
                        <div class="stat-label">Tanggal Dibuat</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= count($analytics) ?></div>
                        <div class="stat-label">Hari Aktif (30 hari)</div>
                    </div>
                </div>

                <?php if (!empty($analytics)): ?>
                <div class="chart-container">
                    <div class="chart-title">📈 Klik dalam 30 Hari Terakhir</div>
                    <?php 
                    $maxClicks = max(array_column($analytics, 'clicks'));
                    foreach ($analytics as $data): 
                        $percentage = $maxClicks > 0 ? ($data['clicks'] / $maxClicks) * 100 : 0;
                    ?>
                    <div class="chart-bar">
                        <div class="chart-label"><?= date('d/m', strtotime($data['date'])) ?></div>
                        <div class="chart-bar-bg">
                            <div class="chart-bar-fill" style="width: <?= $percentage ?>%"></div>
                        </div>
                        <div class="chart-value"><?= $data['clicks'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="chart-container">
                    <div class="chart-title">📈 Belum ada data klik dalam 30 hari terakhir</div>
                </div>
                <?php endif; ?>
                
                <div class="back-link">
                    <a href="https://premiumisme.co/tools/shortlink">← Kembali ke Shortlink Tool</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
