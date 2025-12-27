<?php

namespace GaiaAlpha;

class File
{
    /**
     * Read file content.
     */
    public static function read(string $path)
    {
        return file_exists($path) ? file_get_contents($path) : false;
    }

    /**
     * Read file and decode as JSON.
     */
    public static function readJson(string $path, bool $assoc = true)
    {
        $content = self::read($path);
        if ($content !== false) {
            return json_decode($content, $assoc) ?? [];
        }
        return [];
    }

    /**
     * Write content to file.
     */
    public static function write(string $path, string $content, bool $append = false)
    {
        $flags = $append ? FILE_APPEND : 0;
        return file_put_contents($path, $content, $flags);
    }

    /**
     * Encode data as JSON and write to file.
     */
    public static function writeJson(string $path, $data, int $flags = JSON_PRETTY_PRINT)
    {
        return self::write($path, json_encode($data, $flags));
    }

    /**
     * Append content to file.
     */
    public static function append(string $path, string $content)
    {
        return self::write($path, $content, true);
    }

    /**
     * Delete file or directory (if empty/unlinkable).
     */
    public static function delete(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }
        return unlink($path);
    }

    /**
     * Recursively delete a directory.
     */
    public static function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            (is_dir($path) && !is_link($path)) ? self::deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }

    /**
     * Move a file or directory.
     */
    public static function move(string $path, string $target): bool
    {
        return rename($path, $target);
    }

    /**
     * Check if file or directory exists.
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Check if path is a directory.
     */
    public static function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Check if path is a regular file.
     */
    public static function isFile(string $path): bool
    {
        return is_file($path);
    }

    /**
     * Create directory (recursively by default).
     */
    public static function makeDirectory(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }
        return mkdir($path, $mode, $recursive);
    }

    /**
     * Find path names matching a pattern.
     */
    public static function glob(string $pattern, int $flags = 0): array
    {
        return glob($pattern, $flags) ?: [];
    }

    /**
     * Get file size.
     */
    public static function size(string $path): int
    {
        return file_exists($path) ? filesize($path) : 0;
    }

    /**
     * Read file into an array.
     */
    public static function lines(string $path): array
    {
        return file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
    }
    /**
     * Get MIME type based on file extension.
     */
    public static function mimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf',
            'html' => 'text/html',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'txt' => 'text/plain',
        ];

        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
    /**
     * Require a file once if it exists.
     */
    public static function requireOnce(string $path): bool
    {
        if (self::exists($path)) {
            require_once $path;
            return true;
        }
        return false;
    }
}
