<?php
// Autodowload PSR-4
spl_autoload_register(function ($class) {
    $prefix = 'TokenKeep\\';
    
    $base_dir = __DIR__ . DIRECTORY_SEPARATOR;
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    
    $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

$configPath = dirname(__DIR__) . '/config.php';
if (!file_exists($configPath)) {
    $configPath = dirname(__DIR__) . '/config.example.php';
}
$config = require $configPath;

$db = \TokenKeep\Database::getConnection($config['db_path']);
$tokenManager = new \TokenKeep\TokenManager($db, $config['log_path']);

return [
    'config' => $config,
    'db' => $db,
    'tokenManager' => $tokenManager
];