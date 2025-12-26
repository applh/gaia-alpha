<?php

namespace GaiaAlpha;

class Request
{
    private static ?array $jsonBody = null;

    /**
     * Get value from JSON body (php://input) or $_POST
     */
    public static function input(?string $key = null, $default = null)
    {
        if (self::$jsonBody === null) {
            $input = file_get_contents('php://input');
            self::$jsonBody = json_decode($input, true) ?? [];

            // Merge with $_POST if JSON is empty or to prioritize/fallback? 
            // Usually JSON API uses body. Let's keep distinct or merge.
            // For now, let's treat input as JSON body mainly, but fallback to POST if empty?
            // Actually, best practice: merge $_POST into body if content type is form-urlencoded
            // But for simplicity in this project, we primarily use JSON body.
            // Let's stick to JSON body primary, similar to existing Framework::decodeBody
        }

        // Trigger hook for potential modification
        // We might want to cache this run so strict hook runs once
        // But for simplicity, we'll let it run on access or just once on load.
        // Let's run hook once on load.

        $data = self::$jsonBody;

        // Example hook: Request::hook('request_input', [&$data]); 

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    /**
     * Get value from $_GET
     */
    public static function query(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Get integer value from query
     */
    public static function queryInt(string $key, int $default = 0): int
    {
        $val = self::query($key);
        return is_numeric($val) ? (int) $val : $default;
    }

    /**
     * Get all input (merged query + body)
     */
    public static function all(): array
    {
        return array_merge(self::query(), self::input());
    }

    /**
     * Check HTTP Method
     */
    public static function isMethod(string $method): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
    }

    /**
     * Get uploaded file(s) from $_FILES
     */
    public static function file(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_FILES;
        }
        return $_FILES[$key] ?? $default;
    }

    /**
     * Check if a file exists in the request
     */
    public static function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get the HTTP Host (domain)
     */
    public static function host(): ?string
    {
        return $_SERVER['HTTP_HOST'] ?? null;
    }

    /**
     * Get the Request URI
     */
    public static function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Get the Request Path (URI without query string)
     */
    public static function path(): string
    {
        return parse_url(self::uri(), PHP_URL_PATH) ?? '/';
    }

    /**
     * Check if the request is over HTTPS
     */
    public static function isSecure(): bool
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Get the request scheme (http or https)
     */
    public static function scheme(): string
    {
        return self::isSecure() ? 'https' : 'http';
    }

    /**
     * Get the User Agent string
     */
    public static function userAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Get an item from $_SERVER
     */
    public static function server(string $key, $default = null)
    {
        return $_SERVER[$key] ?? $default;
    }

    /**
     * Get a specific request header
     */
    public static function header(string $key, $default = null)
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$serverKey] ?? $default;
    }

    /**
     * Get the client IP address
     */
    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Determine the request context (public, admin, api)
     */
    /**
     * Determine current application context
     * @return string public|api|admin|app|cli|worker
     */
    public static function context()
    {
        if (php_sapi_name() == 'cli' && !defined('GAIA_TEST_HTTP')) {
            return 'cli';
        }

        $path = self::path();

        // 1. Admin Context
        $adminPrefixes = \GaiaAlpha\Env::get('admin_prefixes', ['/@/admin']);
        foreach ((array) $adminPrefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return 'admin';
            }
        }

        // 2. App Context (User Application)
        $appPrefixes = \GaiaAlpha\Env::get('app_prefixes', ['/@/app']);
        foreach ((array) $appPrefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return 'app';
            }
        }

        // 3. API Context
        $apiPrefixes = \GaiaAlpha\Env::get('api_prefixes', ['/@/api']);
        foreach ((array) $apiPrefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return 'api';
            }
        }

        return 'public';
    }

    /**
     * Helper for testing/mocking
     */
    public static function mock(array $data = [], array $query = [], array $files = [], array $server = [])
    {
        self::$jsonBody = $data;
        $_GET = $query;
        $_FILES = $files;
        $_SERVER = array_merge($_SERVER, $server);
    }
}
