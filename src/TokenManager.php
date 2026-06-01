<?php
namespace TokenKeep;

use PDO;

class TokenManager {
    private PDO $db;
    private string $logPath;

    public function __construct(PDO $db, string $logPath) {
        $this->db = $db;
        $this->logPath = $logPath;
        $this->db->exec('PRAGMA foreign_keys = ON;');
    }

    public function getProvider(string $alias): ?array {
        $stmt = $this->db->prepare("SELECT * FROM providers WHERE alias = :alias LIMIT 1");
        $stmt->execute([':alias' => $alias]);
        $provider = $stmt->fetch();
        return $provider ?: null;
    }

    public function getToken(int $providerId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tokens WHERE provider_id = :provider_id LIMIT 1");
        $stmt->execute([':provider_id' => $providerId]);
        $token = $stmt->fetch();
        return $token ?: null;
    }

    public function saveToken(int $providerId, string $accessToken, ?string $refreshToken, int $expiresIn): bool {
        $expiresAt = time() + $expiresIn;
        $updatedAt = time();

        $existing = $this->getToken($providerId);

        if ($existing) {
            $sql = "UPDATE tokens SET access_token = :access_token, expires_at = :expires_at, updated_at = :updated_at";
            $params = [
                ':access_token' => $accessToken,
                ':expires_at' => $expiresAt,
                ':updated_at' => $updatedAt,
                ':provider_id' => $providerId
            ];
            
            if (!empty($refreshToken)) {
                $sql .= ", refresh_token = :refresh_token";
                $params[':refresh_token'] = $refreshToken;
            }
            $sql .= " WHERE provider_id = :provider_id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO tokens (provider_id, access_token, refresh_token, expires_at, updated_at) 
                VALUES (:provider_id, :access_token, :refresh_token, :expires_at, :updated_at)
            ");
            return $stmt->execute([
                ':provider_id' => $providerId,
                ':access_token' => $accessToken,
                ':refresh_token' => $refreshToken,
                ':expires_at' => $expiresAt,
                ':updated_at' => $updatedAt
            ]);
        }
    }

    public function getValidToken(array $provider, int $bufferSeconds = 300): ?string {
        $token = $this->getToken($provider['id']);
        if (!$token) {
            return null;
        }

        if (($token['expires_at'] - time()) > $bufferSeconds) {
            return $token['access_token'];
        }

        // Lazy-Refresh
        OAuthClient::log("Lazy-refresh triggered for provider: " . $provider['alias'], $this->logPath);
        $refreshed = $this->refreshToken($provider, $token['refresh_token']);
        
        return $refreshed ? $refreshed['access_token'] : null;
    }

    public function refreshToken(array $provider, ?string $refreshToken): ?array {
        if (empty($refreshToken)) {
            OAuthClient::log("Cannot refresh token for {$provider['alias']}: refresh_token is missing.", $this->logPath);
            return null;
        }

        $params = [
            'client_id' => $provider['client_id'],
            'client_secret' => $provider['client_secret'],
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        $response = OAuthClient::requestToken($provider['token_url'], $params, $this->logPath);
        if (!$response) {
            return null;
        }

        $newAccessToken = $response['access_token'];
        $newRefreshToken = $response['refresh_token'] ?? $refreshToken;
        $expiresIn = $response['expires_in'] ?? 3600;

        $this->saveToken($provider['id'], $newAccessToken, $newRefreshToken, $expiresIn);

        return [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken
        ];
    }
}