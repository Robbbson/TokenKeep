<?php
session_start();
$bootstrap = require __DIR__ . '/../src/bootstrap.php';
$tokenManager = $bootstrap['tokenManager'];

$alias = $_GET['provider'] ?? '';
if (empty($alias)) {
    http_response_code(400);
    die("Error: Missing provider parameter.");
}

$provider = $tokenManager->getProvider($alias);
if (!$provider) {
    http_response_code(404);
    die("Error: Provider '$alias' not registered.");
}

// CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state_' . $alias] = $state;

$queryParams = [
    'response_type' => 'code',
    'client_id' => $provider['client_id'],
    'redirect_uri' => $provider['redirect_uri'],
    'state' => $state
];

if (!empty($provider['scopes'])) {
    $queryParams['scope'] = $provider['scopes'];
}

$redirectUrl = $provider['auth_url'] . '?' . http_build_query($queryParams);
header('Location: ' . $redirectUrl);
exit;