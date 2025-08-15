<?php
/**
 * Test Database Connection for Shortisme
 */

echo "<h2>Testing Shortisme Database Connection</h2>";

// Test 1: Check if config file exists
echo "<h3>1. Checking Config File</h3>";
$configPath = '../config/config.php';
if (file_exists($configPath)) {
    echo "✅ Config file exists: $configPath<br>";
} else {
    echo "❌ Config file not found: $configPath<br>";
    echo "Current directory: " . __DIR__ . "<br>";
    echo "Files in current directory: <br>";
    foreach (scandir('.') as $file) {
        echo "- $file<br>";
    }
}

// Test 2: Include config and test connection
echo "<h3>2. Testing Database Connection</h3>";
try {
    require_once $configPath;
    echo "✅ Config file included successfully<br>";
    
    $pdo = getDBConnection();
    if ($pdo) {
        echo "✅ Database connection successful<br>";
        
        // Test query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM shortlinks");
        $count = $stmt->fetch()['count'];
        echo "✅ Database query successful. Shortlinks count: $count<br>";
        
        // Show database info
        echo "Database: " . DB_NAME . "<br>";
        echo "User: " . DB_USER . "<br>";
        echo "Host: " . DB_HOST . "<br>";
        
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Check if tables exist
echo "<h3>3. Checking Database Tables</h3>";
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✅ Tables found: " . implode(', ', $tables) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "<br>";
}
?>
