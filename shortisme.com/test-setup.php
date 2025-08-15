<?php
// Test script untuk validasi setup multi-domain
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Setup Multi-Domain</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #1a1a1a; color: white; }
        .test-section { background: #2a2a2a; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .info { color: #2196F3; }
        pre { background: #333; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>🧪 Test Setup Multi-Domain Shortlink</h1>
    
    <div class="test-section">
        <h2>📋 Informasi Server</h2>
        <p><strong>Server Name:</strong> <?php echo $_SERVER['SERVER_NAME'] ?? 'N/A'; ?></p>
        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></p>
        <p><strong>Current Path:</strong> <?php echo __DIR__; ?></p>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
    </div>

    <div class="test-section">
        <h2>🗄️ Database Test</h2>
        <?php
        $dbFile = __DIR__ . '/shortlinks.json';
        if (file_exists($dbFile)) {
            echo '<p class="success">✅ Database file exists: ' . $dbFile . '</p>';
            
            $content = file_get_contents($dbFile);
            if ($content !== false) {
                $data = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo '<p class="success">✅ Database is valid JSON</p>';
                    echo '<p class="info">📊 Total shortlinks: ' . count($data) . '</p>';
                } else {
                    echo '<p class="error">❌ Database is not valid JSON: ' . json_last_error_msg() . '</p>';
                }
            } else {
                echo '<p class="error">❌ Cannot read database file</p>';
            }
        } else {
            echo '<p class="warning">⚠️ Database file does not exist. Creating empty database...</p>';
            file_put_contents($dbFile, json_encode([], JSON_PRETTY_PRINT));
            echo '<p class="success">✅ Empty database created</p>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🔗 API Test</h2>
        <?php
        // Test API endpoint
        $apiUrl = 'https://shortisme.com/api.php';
        $testData = [
            'action' => 'get',
            'slug' => 'test123'
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => 'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $url = $apiUrl . '?' . http_build_query($testData);
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            echo '<p class="success">✅ API endpoint is accessible</p>';
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
        } else {
            echo '<p class="error">❌ Cannot access API endpoint</p>';
            echo '<p class="info">Make sure shortisme.com is pointing to this server</p>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🌐 Cross-Domain Test</h2>
        <p class="info">Testing CORS headers for cross-domain requests...</p>
        <button class="btn" onclick="testCORS()">Test CORS</button>
        <div id="cors-result"></div>
    </div>

    <div class="test-section">
        <h2>🔧 Create Test Shortlink</h2>
        <button class="btn" onclick="createTestLink()">Create Test Link</button>
        <div id="test-link-result"></div>
    </div>

    <div class="test-section">
        <h2>📝 Manual Tests</h2>
        <p>Please manually test these URLs:</p>
        <ul>
            <li><strong>Tools Interface:</strong> <a href="https://premiumisme.co/tools/shortlink/" target="_blank">https://premiumisme.co/tools/shortlink/</a></li>
            <li><strong>Shortlink Domain:</strong> <a href="https://shortisme.com" target="_blank">https://shortisme.com</a></li>
            <li><strong>API Endpoint:</strong> <a href="https://shortisme.com/api.php" target="_blank">https://shortisme.com/api.php</a></li>
        </ul>
    </div>

    <script>
        function testCORS() {
            const resultDiv = document.getElementById('cors-result');
            resultDiv.innerHTML = '<p class="info">Testing CORS...</p>';
            
            fetch('https://shortisme.com/api.php?action=get&slug=test', {
                method: 'GET',
                mode: 'cors',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = '<p class="success">✅ CORS test successful</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">❌ CORS test failed: ' + error.message + '</p>';
            });
        }

        function createTestLink() {
            const resultDiv = document.getElementById('test-link-result');
            resultDiv.innerHTML = '<p class="info">Creating test link...</p>';
            
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('data', JSON.stringify({
                id: Date.now().toString(),
                originalUrl: 'https://example.com/test',
                slug: 'test' + Math.random().toString(36).substr(2, 4),
                shortUrl: 'https://shortisme.com/test123',
                clicks: 0,
                createdAt: new Date().toISOString()
            }));

            fetch('https://shortisme.com/api.php', {
                method: 'POST',
                body: formData,
                mode: 'cors',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<p class="success">✅ Test link created successfully</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    resultDiv.innerHTML = '<p class="error">❌ Failed to create test link: ' + data.error + '</p>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">❌ Error creating test link: ' + error.message + '</p>';
            });
        }
    </script>
</body>
</html>
