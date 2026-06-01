<?php
if (php_sapi_name() !== 'cli') {
    die("Forbidden: CLI only.\n");
}

$bootstrap = require __DIR__ . '/src/bootstrap.php';
$db = $bootstrap['db'];

echo "=== TokenKeep: Register/Update OAuth2 Provider ===\n";
$alias = readline("Enter Provider Alias (e.g. zoho): ");
$client_id = readline("Client ID: ");
$client_secret = readline("Client Secret: ");
$auth_url = readline("Auth URL: ");
$token_url = readline("Token URL: ");
$scopes = readline("Scopes (optional, space separated): ");
$redirect_uri = readline("Redirect URI: ");

if (empty($alias) || empty($client_id) || empty($client_secret) || empty($auth_url) || empty($token_url) || empty($redirect_uri)) {
    die("Error: All fields (except scopes) are required!\n");
}

$stmt = $db->prepare("
    INSERT OR REPLACE INTO providers (alias, client_id, client_secret, auth_url, token_url, scopes, redirect_uri)
    VALUES (:alias, :client_id, :client_secret, :auth_url, :token_url, :scopes, :redirect_uri)
");

$success = $stmt->execute([
    ':alias' => $alias,
    ':client_id' => $client_id,
    ':client_secret' => $client_secret,
    ':auth_url' => $auth_url,
    ':token_url' => $token_url,
    ':scopes' => !empty($scopes) ? $scopes : null,
    ':redirect_uri' => $redirect_uri
]);

if ($success) {
    echo "Provider '$alias' successfully registered/updated!\n";
} else {
    echo "An error occurred during database insert.\n";
}