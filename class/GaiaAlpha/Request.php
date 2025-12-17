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
     * Helper for testing/mocking
     */
    public static function mock(array $data = [], array $query = [], array $files = [])
    {
        self::$jsonBody = $data;
        $_GET = $query;
        $_FILES = $files;
    }
}
