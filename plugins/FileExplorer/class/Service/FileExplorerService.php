<?php

namespace FileExplorer\Service;

use GaiaAlpha\File;

class FileExplorerService
{
    public static function listDirectory(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $items = [];
        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $file;
            $isDir = is_dir($fullPath);

            $items[] = [
                'name' => $file,
                'path' => $fullPath,
                'isDir' => $isDir,
                'hasChildren' => $isDir,
                'size' => $isDir ? 0 : filesize($fullPath),
                'mtime' => filemtime($fullPath),
                'ext' => $isDir ? '' : strtolower(pathinfo($file, PATHINFO_EXTENSION))
            ];
        }

        return $items;
    }

    public static function getFileContent(string $path): string
    {
        if (!is_file($path)) {
            return '';
        }
        return file_get_contents($path);
    }

    public static function saveFile(string $path, string $content): bool
    {
        return file_put_contents($path, $content) !== false;
    }

    public static function createDirectory(string $path): bool
    {
        return File::makeDirectory($path);
    }

    public static function deleteItem(string $path): bool
    {
        if (is_dir($path)) {
            return self::deleteDir($path);
        }
        return unlink($path);
    }

    private static function deleteDir(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            (is_dir($path)) ? self::deleteDir($path) : unlink($path);
        }
        return rmdir($dir);
    }

    public static function renameItem(string $oldPath, string $newPath): bool
    {
        return rename($oldPath, $newPath);
    }

    public static function moveItem(string $source, string $target): bool
    {
        return rename($source, $target);
    }
}
