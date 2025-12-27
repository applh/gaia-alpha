<?php

namespace GaiaAlpha\Cli;

use Exception;
use GaiaAlpha\Env;
use GaiaAlpha\File;
use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;

class FileCommands
{
    private static function getDataPath(): string
    {
        return Env::get('path_data') ?: (getenv('GAIA_DATA_PATH') ?: Env::get('root_dir') . '/my-data');
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

    public static function handleWrite(): void
    {
        if (!Input::has(0) || !Input::has(1)) {
            Output::writeln("Usage: file:write <path> <content>");
            exit(1);
        }

        $path = Input::get(0);
        $content = Input::get(1);
        try {
            $fullPath = self::validatePath($path);

            // Create directory if it doesn't exist
            $dir = dirname($fullPath);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir);
            }

            File::write($fullPath, $content);
            Output::success("File written: $path");
        } catch (Exception $e) {
            Output::error($e->getMessage());
            exit(1);
        }
    }

    public static function handleRead(): void
    {
        if (!Input::has(0)) {
            Output::writeln("Usage: file:read <path>");
            exit(1);
        }

        $path = Input::get(0);
        try {
            $fullPath = self::validatePath($path);

            if (!File::exists($fullPath)) {
                Output::error("File not found: $path");
                exit(1);
            }

            if (!File::isFile($fullPath)) {
                Output::error("Not a file: $path");
                exit(1);
            }

            echo File::read($fullPath);
        } catch (Exception $e) {
            Output::error($e->getMessage());
            exit(1);
        }
    }

    public static function handleList(): void
    {
        $subPath = Input::get(0, '');
        $basePath = self::getDataPath();
        try {
            $fullPath = $subPath ? self::validatePath($subPath) : $basePath;

            if (!File::isDirectory($fullPath)) {
                Output::error("Not a directory: $subPath");
                exit(1);
            }

            $items = scandir($fullPath);
            Output::title("Contents of " . ($subPath ?: '/'));

            $headers = ["Type", "Name", "Size"];
            $rows = [];

            foreach ($items as $item) {
                if ($item === '.' || $item === '..')
                    continue;

                $itemPath = $fullPath . '/' . $item;
                $type = File::isDirectory($itemPath) ? 'DIR' : 'FILE';
                $size = File::isFile($itemPath) ? self::formatBytes(File::size($itemPath)) : '';

                $rows[] = [$type, $item, $size];
            }

            Output::table($headers, $rows);
        } catch (Exception $e) {
            Output::error($e->getMessage());
            exit(1);
        }
    }

    public static function handleDelete(): void
    {
        if (!Input::has(0)) {
            Output::writeln("Usage: file:delete <path>");
            exit(1);
        }

        $path = Input::get(0);
        try {
            $fullPath = self::validatePath($path);

            if (!File::exists($fullPath)) {
                Output::error("File not found: $path");
                exit(1);
            }

            if (File::isDirectory($fullPath)) {
                Output::error("Cannot delete directories. Use file:delete on individual files.");
                exit(1);
            }

            File::delete($fullPath);
            Output::success("File deleted: $path");
        } catch (Exception $e) {
            Output::error($e->getMessage());
            exit(1);
        }
    }

    public static function handleMove(): void
    {
        if (!Input::has(0) || !Input::has(1)) {
            Output::writeln("Usage: file:move <source> <destination>");
            exit(1);
        }

        $source = Input::get(0);
        $destination = Input::get(1);

        try {
            $sourcePath = self::validatePath($source);
            $destPath = self::validatePath($destination);

            if (!File::exists($sourcePath)) {
                Output::error("Source file not found: $source");
                exit(1);
            }

            // Create destination directory if needed
            $destDir = dirname($destPath);
            if (!File::isDirectory($destDir)) {
                File::makeDirectory($destDir);
            }

            File::move($sourcePath, $destPath);
            Output::success("File moved: $source -> $destination");
        } catch (Exception $e) {
            Output::error($e->getMessage());
            exit(1);
        }
    }
}
