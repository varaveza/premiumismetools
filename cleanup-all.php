<?php
/**
 * All-in-One Cleanup Script
 * Menghapus file upload, JSON, log, dan file lama lebih dari 30 hari
 */

// Konfigurasi
$config = [
    'maxAge' => 30, // Hapus file lebih dari 30 hari
    'logMaxAge' => 7, // Hapus log lebih dari 7 hari
    'logMaxSize' => 10 * 1024 * 1024, // Hapus log lebih dari 10MB
    'backupMaxAge' => 7, // Hapus backup lebih dari 7 hari
    'tempMaxAge' => 1, // Hapus temp lebih dari 1 hari
    'allowedExtensions' => ['.txt', '.csv', '.xlsx', '.xls', '.zip', '.rar', '.7z', '.json', '.log', '.backup', '.bak', '.tmp']
];

// Direktori yang di-cleanup
$directories = [
    'uploads' => __DIR__ . '/uploads/',
    'temp' => __DIR__ . '/temp/',
    'tmp' => __DIR__ . '/tmp/',
    'split-mail' => __DIR__ . '/split-mail/uploads/',
    'remove-duplicate' => __DIR__ . '/remove-duplicate/uploads/',
    'generator-email' => __DIR__ . '/generator-email/uploads/',
    'refund-calculator' => __DIR__ . '/refund-calculator/uploads/',
    'shortlink' => __DIR__ . '/shortlink/'
];

// Log file
$logFile = __DIR__ . '/cleanup-all.log';
$logEntry = date('Y-m-d H:i:s') . " - All-in-One cleanup started\n";
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

$stats = [
    'totalDeleted' => 0,
    'totalSize' => 0,
    'byType' => [
        'upload' => 0,
        'json' => 0,
        'log' => 0,
        'backup' => 0,
        'temp' => 0
    ]
];

/**
 * Cleanup file berdasarkan direktori dan aturan
 */
function cleanupDirectory($dirPath, $dirName, $config, &$stats) {
    if (!is_dir($dirPath)) {
        return;
    }
    
    $files = glob($dirPath . '*');
    $cutoffTime = time() - ($config['maxAge'] * 24 * 60 * 60);
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileTime = filemtime($file);
            $fileSize = filesize($file);
            $fileName = basename($file);
            $fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            // Skip file tertentu
            if ($fileName === 'shortlinks.json' || $fileName === 'cleanup-all.log') {
                continue;
            }
            
            $shouldDelete = false;
            $deleteReason = '';
            
            // Cek berdasarkan extension dan aturan
            switch ($fileExt) {
                case 'json':
                    if ($fileTime < $cutoffTime) {
                        $shouldDelete = true;
                        $deleteReason = 'old JSON';
                        $stats['byType']['json']++;
                    }
                    break;
                    
                case 'log':
                    $logCutoff = time() - ($config['logMaxAge'] * 24 * 60 * 60);
                    if ($fileTime < $logCutoff || $fileSize > $config['logMaxSize']) {
                        $shouldDelete = true;
                        $deleteReason = $fileTime < $logCutoff ? 'old log' : 'large log';
                        $stats['byType']['log']++;
                    }
                    break;
                    
                case 'backup':
                case 'bak':
                    $backupCutoff = time() - ($config['backupMaxAge'] * 24 * 60 * 60);
                    if ($fileTime < $backupCutoff) {
                        $shouldDelete = true;
                        $deleteReason = 'old backup';
                        $stats['byType']['backup']++;
                    }
                    break;
                    
                case 'tmp':
                    $tempCutoff = time() - ($config['tempMaxAge'] * 24 * 60 * 60);
                    if ($fileTime < $tempCutoff) {
                        $shouldDelete = true;
                        $deleteReason = 'old temp';
                        $stats['byType']['temp']++;
                    }
                    break;
                    
                default:
                    // File upload biasa
                    if ($fileTime < $cutoffTime) {
                        $shouldDelete = true;
                        $deleteReason = 'old upload';
                        $stats['byType']['upload']++;
                    }
                    break;
            }
            
            // Hapus file jika memenuhi kriteria
            if ($shouldDelete) {
                if (unlink($file)) {
                    $stats['totalDeleted']++;
                    $stats['totalSize'] += $fileSize;
                    $logEntry = date('Y-m-d H:i:s') . " - Deleted $deleteReason: $fileName (" . formatBytes($fileSize) . ") from $dirName\n";
                    file_put_contents($GLOBALS['logFile'], $logEntry, FILE_APPEND | LOCK_EX);
                }
            }
        }
    }
}

/**
 * Hapus direktori kosong
 */
function cleanupEmptyDirectories($directories) {
    foreach ($directories as $dirName => $dirPath) {
        if (is_dir($dirPath) && count(glob($dirPath . '*')) === 0) {
            if (rmdir($dirPath)) {
                $logEntry = date('Y-m-d H:i:s') . " - Removed empty directory: $dirName\n";
                file_put_contents($GLOBALS['logFile'], $logEntry, FILE_APPEND | LOCK_EX);
            }
        }
    }
}

/**
 * Format bytes
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Tampilkan statistik file yang tersisa
 */
function showRemainingFiles($directories) {
    echo "<h3>📊 File Statistics:</h3>";
    
    foreach ($directories as $dirName => $dirPath) {
        if (is_dir($dirPath)) {
            $files = glob($dirPath . '*');
            if (count($files) > 0) {
                echo "<h4>$dirName:</h4>";
                echo "<ul>";
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $fileName = basename($file);
                        $fileSize = formatBytes(filesize($file));
                        $fileAge = round((time() - filemtime($file)) / (24 * 60 * 60));
                        $fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        echo "<li>$fileName ($fileSize, $fileAge days old, .$fileExt)</li>";
                    }
                }
                echo "</ul>";
            }
        }
    }
}

// Jalankan cleanup untuk semua direktori
foreach ($directories as $dirName => $dirPath) {
    cleanupDirectory($dirPath, $dirName, $config, $stats);
}

// Hapus direktori kosong
cleanupEmptyDirectories($directories);

// Log completion
$logEntry = date('Y-m-d H:i:s') . " - All-in-One cleanup completed.\n";
$logEntry .= "Total deleted: {$stats['totalDeleted']} files\n";
$logEntry .= "Total size freed: " . formatBytes($stats['totalSize']) . "\n";
$logEntry .= "By type: Upload: {$stats['byType']['upload']}, JSON: {$stats['byType']['json']}, Log: {$stats['byType']['log']}, Backup: {$stats['byType']['backup']}, Temp: {$stats['byType']['temp']}\n";
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Output untuk web
if (php_sapi_name() !== 'cli') {
    echo "<h2>🧹 All-in-One Cleanup Completed</h2>";
    echo "<div style='background: #1a1a1a; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>📈 Summary:</h3>";
    echo "<p><strong>Total deleted:</strong> {$stats['totalDeleted']} files</p>";
    echo "<p><strong>Size freed:</strong> " . formatBytes($stats['totalSize']) . "</p>";
    
    echo "<h3>📋 By Type:</h3>";
    echo "<ul>";
    echo "<li>Upload files: {$stats['byType']['upload']}</li>";
    echo "<li>JSON files: {$stats['byType']['json']}</li>";
    echo "<li>Log files: {$stats['byType']['log']}</li>";
    echo "<li>Backup files: {$stats['byType']['backup']}</li>";
    echo "<li>Temp files: {$stats['byType']['temp']}</li>";
    echo "</ul>";
    
    echo "<p><strong>Log:</strong> <code>cleanup-all.log</code></p>";
    echo "</div>";
    
    // Tampilkan file yang tersisa
    showRemainingFiles($directories);
    
    echo "<div style='margin-top: 20px; padding: 10px; background: #2a2a2a; border-radius: 5px;'>";
    echo "<p><strong>💡 Tips:</strong></p>";
    echo "<ul>";
    echo "<li>Jalankan script ini secara manual atau via cron job</li>";
    echo "<li>File yang dihapus TIDAK bisa dikembalikan</li>";
    echo "<li>Check log file untuk detail aktivitas</li>";
    echo "</ul>";
    echo "</div>";
}
?>
