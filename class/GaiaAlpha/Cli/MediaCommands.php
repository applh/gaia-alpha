<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Media;

class MediaCommands
{
    private static Media $media;

    public static function setMedia(Media $media)
    {
        self::$media = $media;
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

    public static function handleStats(): void
    {
        $stats = self::$media->getStats();
        echo "Media Storage Stats:\n";
        echo "--------------------\n";
        echo "Uploads: " . $stats['uploads']['count'] . " files (" . self::formatBytes($stats['uploads']['size']) . ")\n";
        echo "Cache:   " . $stats['cache']['count'] . " files (" . self::formatBytes($stats['cache']['size']) . ")\n";
    }

    public static function handleClearCache(): void
    {
        $count = self::$media->clearCache();
        echo "Cache cleared. Deleted $count files.\n";
    }
}
