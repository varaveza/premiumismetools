<?php
echo "<h2>Test Python CLI Execution</h2>";

// Test 1: Check Python path
echo "<h3>1. Python Path Test</h3>";
$python_path = shell_exec('python --version 2>&1');
echo "<pre>Python version: " . htmlspecialchars($python_path) . "</pre>";

// Test 2: Check if CLI file exists
echo "<h3>2. CLI File Check</h3>";
$cli_path = __DIR__ . '/py/cli_create.py';
echo "<pre>CLI path: " . htmlspecialchars($cli_path) . "</pre>";
echo "<pre>File exists: " . (file_exists($cli_path) ? 'YES' : 'NO') . "</pre>";

// Test 3: Test CLI execution
echo "<h3>3. CLI Execution Test</h3>";
$py = escapeshellcmd('python');
$cli = escapeshellarg($cli_path);
$domain = escapeshellarg('test.com');
$password = escapeshellarg('TestPass123');

$cmd = $py . ' ' . $cli . ' ' . $domain . ' ' . $password;
echo "<pre>Command: " . htmlspecialchars($cmd) . "</pre>";

$output = shell_exec($cmd . ' 2>&1');
echo "<pre>Output:</pre>";
echo "<pre style='background:#f0f0f0; padding:10px; border:1px solid #ccc;'>" . htmlspecialchars($output) . "</pre>";

// Test 4: JSON decode test
echo "<h3>4. JSON Decode Test</h3>";
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<pre>JSON decode: SUCCESS</pre>";
    echo "<pre>Result: " . print_r($json, true) . "</pre>";
} else {
    echo "<pre>JSON decode: FAILED</pre>";
    echo "<pre>Error: " . json_last_error_msg() . "</pre>";
}

// Test 5: Environment check
echo "<h3>5. Environment Check</h3>";
echo "<pre>Current directory: " . getcwd() . "</pre>";
echo "<pre>PHP version: " . PHP_VERSION . "</pre>";
echo "<pre>OS: " . PHP_OS . "</pre>";

// Test 6: File permissions
echo "<h3>6. File Permissions</h3>";
if (file_exists($cli_path)) {
    echo "<pre>CLI file permissions: " . substr(sprintf('%o', fileperms($cli_path)), -4) . "</pre>";
    echo "<pre>CLI file owner: " . posix_getpwuid(fileowner($cli_path))['name'] . "</pre>";
}
?>
