<?php

namespace GaiaAlpha\Cli;

use Exception;
use GaiaAlpha\Env;

class FileCommands
{
    private static function getDataPath(): string
    {
        return defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : Env::get('root_dir') . '/my-data';
    }

    private static function validatePath(string $path): string
    {
        // Remove any directory traversal attempts
        $path = str_replace(['../', '..\\'], '', $path);
        $fullPath = self::getDataPath() . '/' . ltrim($path, '/');

        // Ensure the path is within my-data directory
        $realDataPath = realpath(self::getDataPath());
        $realFullPath = realpath(dirname($fullPath));

        if ($realFullPath === false || strpos($realFullPath, $realDataPath) !== 0) {
            throw new Exception("Access denied: Path must be within my-data directory");
        }

        return $fullPath;
    }

    private static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function handleWrite(array $args): void
    {
        if (!isset($args[2]) || !isset($args[3])) {
            die("Usage: file:write <path> <content>\n");
        }

        $path = $args[2];
        $content = $args[3];
        $fullPath = self::validatePath($path);

        // Create directory if it doesn't exist
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);
        echo "File written: $path\n";
    }

    public static function handleRead(array $args): void
    {
        if (!isset($args[2])) {
            die("Usage: file:read <path>\n");
        }

        $path = $args[2];
        $fullPath = self::validatePath($path);

        if (!file_exists($fullPath)) {
            die("File not found: $path\n");
        }

        if (!is_file($fullPath)) {
            die("Not a file: $path\n");
        }

        echo file_get_contents($fullPath);
    }

    public static function handleList(array $args): void
    {
        $subPath = $args[2] ?? '';
        $basePath = self::getDataPath();
        $fullPath = $subPath ? self::validatePath($subPath) : $basePath;

        if (!is_dir($fullPath)) {
            die("Not a directory: $subPath\n");
        }

        $items = scandir($fullPath);
        echo "Contents of " . ($subPath ?: '/') . ":\n";
        echo "--------------------\n";

        foreach ($items as $item) {
            if ($item === '.' || $item === '..')
                continue;

            $itemPath = $fullPath . '/' . $item;
            $type = is_dir($itemPath) ? 'DIR ' : 'FILE';
            $size = is_file($itemPath) ? self::formatBytes(filesize($itemPath)) : '';

            echo sprintf("%-5s %-20s %s\n", $type, $item, $size);
        }
    }

    public static function handleDelete(array $args): void
    {
        if (!isset($args[2])) {
            die("Usage: file:delete <path>\n");
        }

        $path = $args[2];
        $fullPath = self::validatePath($path);

        if (!file_exists($fullPath)) {
            die("File not found: $path\n");
        }

        if (is_dir($fullPath)) {
            die("Cannot delete directories. Use file:delete on individual files.\n");
        }

        unlink($fullPath);
        echo "File deleted: $path\n";
    }

    public static function handleMove(array $args): void
    {
        if (!isset($args[2]) || !isset($args[3])) {
            die("Usage: file:move <source> <destination>\n");
        }

        $source = $args[2];
        $destination = $args[3];

        $sourcePath = self::validatePath($source);
        $destPath = self::validatePath($destination);

        if (!file_exists($sourcePath)) {
            die("Source file not found: $source\n");
        }

        // Create destination directory if needed
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        rename($sourcePath, $destPath);
        echo "File moved: $source -> $destination\n";
    }
}
