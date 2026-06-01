<?php
namespace TokenKeep;

class OAuthClient {
    public static function log(string $message, string $logPath): void {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logPath, "[$timestamp] $message\n", FILE_APPEND);
    }

    public static function requestToken(string $url, array $params, string $logPath): ?array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            self::log("cURL Error request to $url: $error", $logPath);
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        $data = json_decode($response, true);

        if ($httpCode >= 400 || !isset($data['access_token'])) {
            self::log("OAuth API Error from $url [Status $httpCode]: " . ($response ?: 'Empty Response'), $logPath);
            return null;
        }

        return $data;
    }
}