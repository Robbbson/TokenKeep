<?php
namespace TokenKeep;

use PDO;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(string $dbPath): PDO {
        if (self::$instance === null) {
            self::$instance = new PDO("sqlite:" . $dbPath);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::initialize();
        }
        return self::$instance;
    }

    private static function initialize(): void {
        $db = self::$instance;
        
        // Create providers table
        $db->exec("CREATE TABLE IF NOT EXISTS providers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            alias TEXT UNIQUE NOT NULL,
            client_id TEXT NOT NULL,
            client_secret TEXT NOT NULL,
            auth_url TEXT NOT NULL,
            token_url TEXT NOT NULL,
            scopes TEXT,
            redirect_uri TEXT NOT NULL
        )");

        // Create tokens table (1 token per provider)
        $db->exec("CREATE TABLE IF NOT EXISTS tokens (
            provider_id INTEGER PRIMARY KEY,
            access_token TEXT NOT NULL,
            refresh_token TEXT,
            expires_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL,
            FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
        )");
    }
}