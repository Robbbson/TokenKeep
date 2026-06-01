<?php
$bootstrap = require __DIR__ . '/../../src/bootstrap.php';
$tokenManager = $bootstrap['tokenManager'];
$config = $bootstrap['config'];

header('Content-Type: application/json');

// Cross-platform way to get Bearer Token from headers
function getBearerToken(): ?string {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }
    return null;
}

$bearerToken = getBearerToken();
if (!$bearerToken || $bearerToken !== $config['api_secret_token']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized: Invalid or missing API secret key']);
    exit;
}

$alias = $_GET['provider'] ?? '';
if (empty($alias)) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request: Missing provider parameter']);
    exit;
}

$provider = $tokenManager->getProvider($alias);
if (!$provider) {
    http_response_code(404);
    echo json_encode(['error' => "Not Found: Provider '$alias' not found"]);
    exit;
}

try {
    // 300 seconds (5 minutes) as a buffer before the token expires
    $accessToken = $tokenManager->getValidToken($provider, 300);

    if (!$accessToken) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error: Failed to retrieve or refresh access token']);
        exit;
    }

    echo json_encode(['access_token' => $accessToken]);
} catch (\Exception $e) {
    \TokenKeep\OAuthClient::log("Critical API Error: " . $e->getMessage(), $config['log_path']);
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error', 'message' => $e->getMessage()]);
}