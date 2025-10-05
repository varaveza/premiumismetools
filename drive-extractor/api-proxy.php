<?php
// API Proxy untuk Drive Extractor
// File ini berfungsi sebagai proxy antara frontend dan Node.js API

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get fileId from query parameter
$fileId = isset($_GET['fileId']) ? trim($_GET['fileId']) : '';

if (empty($fileId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter "fileId" is required.']);
    exit();
}

// Validate fileId format
if (!preg_match('/^[a-zA-Z0-9-_]+$/', $fileId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid fileId format.']);
    exit();
}

// Try to connect to Node.js API
$apiUrl = 'http://localhost:1203/api/get-drive-content?fileId=' . urlencode($fileId);

// Initialize cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_HTTPHEADER => [
        'User-Agent: Drive-Extractor-Proxy/1.0'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Handle cURL errors
if ($response === false || !empty($error)) {
    http_response_code(503);
    echo json_encode([
        'error' => 'Backend API tidak tersedia',
        'details' => 'Node.js API server tidak berjalan atau tidak dapat diakses. Error: ' . $error,
        'suggestion' => 'Pastikan API server berjalan di localhost:1203'
    ]);
    exit();
}

// Handle HTTP errors
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        'error' => 'API request failed',
        'details' => 'HTTP ' . $httpCode,
        'response' => $response
    ]);
    exit();
}

// Return the response from Node.js API
header('Content-Type: text/plain; charset=utf-8');
echo $response;
?>


