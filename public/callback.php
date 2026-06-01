<?php
session_start();
$bootstrap = require __DIR__ . '/../src/bootstrap.php';
$tokenManager = $bootstrap['tokenManager'];
$config = $bootstrap['config'];

$alias = $_GET['provider'] ?? '';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (empty($alias) || empty($code) || empty($state)) {
    http_response_code(400);
    die("Error: Invalid or missing authorization parameters.");
}

$savedState = $_SESSION['oauth_state_' . $alias] ?? null;
if (!$savedState || $savedState !== $state) {
    http_response_code(403);
    die("Error: State mismatch or session expired (CSRF protection).");
}

// Preventing state reuse
unset($_SESSION['oauth_state_' . $alias]);

$provider = $tokenManager->getProvider($alias);
if (!$provider) {
    http_response_code(404);
    die("Error: Provider '$alias' not registered.");
}

$tokenParams = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $provider['redirect_uri'],
    'client_id' => $provider['client_id'],
    'client_secret' => $provider['client_secret'],
];

$response = \TokenKeep\OAuthClient::requestToken($provider['token_url'], $tokenParams, $config['log_path']);

if (!$response) {
    http_response_code(500);
    die("Error: Failed to fetch access token from provider.");
}

$accessToken = $response['access_token'];
$refreshToken = $response['refresh_token'] ?? null;
$expiresIn = $response['expires_in'] ?? 3600;

$success = $tokenManager->saveToken($provider['id'], $accessToken, $refreshToken, $expiresIn);

if ($success) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => "Authorization flow completed. Token secured for provider: $alias"
    ]);
} else {
    http_response_code(500);
    die("Error: Failed to write tokens to database.");
}