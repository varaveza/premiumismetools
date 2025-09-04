<?php
/**
 * Database Setup Script for Spotify Creator API
 * Creates necessary tables for rate limiting and daily limits
 */

require_once 'config.php';

$cfg = load_config();

try {
    $pdo = new PDO('sqlite:' . $cfg['SQLITE_PATH']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create ip_submissions table for 1 user per day limit
    $pdo->exec("CREATE TABLE IF NOT EXISTS ip_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT NOT NULL,
        ua_hash TEXT NOT NULL,
        submitted_at DATETIME NOT NULL
    )");
    
    // Create daily_submissions table for 100 accounts per day limit
    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL UNIQUE,
        count INTEGER DEFAULT 0
    )");
    
    // Create web_submissions table for tracking all submissions
    $pdo->exec("CREATE TABLE IF NOT EXISTS web_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT NOT NULL,
        email TEXT,
        is_student INTEGER DEFAULT 0,
        success INTEGER NOT NULL,
        created_at TEXT NOT NULL
    )");
    
    // Initialize today's count to 0 if not exists
    $today = date('Y-m-d');
    $pdo->prepare("INSERT OR IGNORE INTO daily_submissions (date, count) VALUES (?, 0)")
        ->execute([$today]);
    
    echo "✅ Database setup completed successfully!\n";
    echo "📊 Tables created:\n";
    echo "   - ip_submissions (1 user per day limit)\n";
    echo "   - daily_submissions (100 accounts per day limit)\n";
    echo "   - web_submissions (tracking all submissions)\n";
    echo "📅 Today's date initialized: $today\n";
    
} catch (Exception $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
