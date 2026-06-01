// public/mock_provider.php
<?php
header('Content-Type: application/json');

// Імітуємо видачу або оновлення токенів
echo json_encode([
    'access_token' => 'mock_access_token_' . bin2hex(random_bytes(8)),
    'refresh_token' => 'mock_refresh_token_new_' . bin2hex(random_bytes(8)),
    'expires_in' => 3600 // Токен діє 1 годину
]);