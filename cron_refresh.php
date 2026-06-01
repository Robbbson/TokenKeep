<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Forbidden: This script can only be run via CLI.\n");
}

$bootstrap = require __DIR__ . '/src/bootstrap.php';
$db = $bootstrap['db'];
$tokenManager = $bootstrap['tokenManager'];
$config = $bootstrap['config'];

\TokenKeep\OAuthClient::log("Cron auto-refresh started.", $config['log_path']);

// Select tokens that will expire in less than 20 minutes (1200 seconds)
$threshold = time() + 1200;

$stmt = $db->prepare("
    SELECT p.*, t.refresh_token 
    FROM providers p
    JOIN tokens t ON p.id = t.provider_id
    WHERE t.expires_at < :threshold
");
$stmt->execute([':threshold' => $threshold]);
$providersToRefresh = $stmt->fetchAll();

$success = 0;
$failed = 0;

foreach ($providersToRefresh as $provider) {
    \TokenKeep\OAuthClient::log("Cron: Refreshing token for alias: " . $provider['alias'], $config['log_path']);
    $result = $tokenManager->refreshToken($provider, $provider['refresh_token']);
    if ($result) {
        $success++;
    } else {
        $failed++;
    }
}

\TokenKeep\OAuthClient::log("Cron run finished. Success: $success, Failed: $failed", $config['log_path']);
echo "Cron execution finished. Refreshed: $success, Failed: $failed.\n";