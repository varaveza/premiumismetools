<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Expose-Headers: *');
header('Access-Control-Max-Age: 86400');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// API endpoint - Production: api.ngontol.com, Development: localhost
$isProduction = !empty($_SERVER['HTTP_HOST']) && 
                $_SERVER['HTTP_HOST'] !== 'localhost' && 
                strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false;

$API_URL = $isProduction 
    ? 'https://api.ngontol.com/api/join'  // Production
    : 'http://localhost:8001/api/join';   // Development

// Get request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required fields
if (!isset($data['link']) || empty($data['link'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Link is required']);
    exit;
}

if (!isset($data['accounts']) || !is_array($data['accounts']) || empty($data['accounts'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Accounts array is required and must not be empty']);
    exit;
}

// Prepare request
$ch = curl_init($API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Handle errors
if ($error) {
    http_response_code(500);
    echo json_encode(['error' => 'Connection error', 'message' => $error]);
    exit;
}

// Set HTTP status code
http_response_code($httpCode);

// Return response
echo $response;
?>

