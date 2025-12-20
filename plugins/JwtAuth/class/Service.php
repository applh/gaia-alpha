<?php

namespace JwtAuth;

use GaiaAlpha\Env;

class Service
{
    /**
     * Encode data to Base64URL
     */
    private static function base64UrlEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Decode data from Base64URL
     */
    private static function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    /**
     * Get the secret key for signing
     */
    public static function getSecret()
    {
        // Try to get from DataStore (DB) first
        try {
            if (class_exists('GaiaAlpha\\Model\\DataStore')) {
                $dbSecret = \GaiaAlpha\Model\DataStore::get(0, 'jwt_settings', 'secret');
                if ($dbSecret) {
                    return $dbSecret;
                }
            }
        } catch (\Exception $e) {
            // Fallback during install/migrations
        }

        $secret = Env::get('jwt_secret');
        if (!$secret) {
            // Default secret for development - SHOULD BE CHANGED IN PRODUCTION
            $secret = 'gaia-alpha-default-jwt-secret-key-1234567890';
        }
        return $secret;
    }

    /**
     * Generate a JWT token
     */
    public static function generateToken(array $payload, int $ttl = 3600): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $payload['iat'] = time();
        $payload['exp'] = (isset($payload['exp'])) ? $payload['exp'] : (time() + $ttl);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Validate a JWT token and return payload if valid
     */
    public static function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

        $signature = self::base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);

        if (!hash_equals($signature, $expectedSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }
}
