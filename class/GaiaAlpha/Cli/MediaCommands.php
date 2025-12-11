<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Media;
use GaiaAlpha\Env;
class MediaCommands
{
    private static function getMedia(): Media
    {
        return new Media(Env::get('path_data'));
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
        $stats = self::getMedia()->getStats();
        echo "Media Storage Stats:\n";
        echo "--------------------\n";
        echo "Uploads: " . $stats['uploads']['count'] . " files (" . self::formatBytes($stats['uploads']['size']) . ")\n";
        echo "Cache:   " . $stats['cache']['count'] . " files (" . self::formatBytes($stats['cache']['size']) . ")\n";
    }

    public static function handleClearCache(): void
    {
        $count = self::getMedia()->clearCache();
        echo "Cache cleared. Deleted $count files.\n";
    }

    public static function handleProcess(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:process <input_file> <output_file> [width] [height] [quality] [fit] [rotate] [flip] [filter]\n";
            exit(1);
        }

        $input = $argv[2];
        $output = $argv[3];
        $width = isset($argv[4]) ? (int) $argv[4] : 0;
        $height = isset($argv[5]) ? (int) $argv[5] : 0;
        $quality = isset($argv[6]) ? (int) $argv[6] : 80;
        $fit = isset($argv[7]) ? $argv[7] : 'contain';
        $rotate = isset($argv[8]) ? (int) $argv[8] : 0;
        $flip = isset($argv[9]) ? $argv[9] : '';
        $filter = isset($argv[10]) ? $argv[10] : '';

        if (!file_exists($input)) {
            echo "Error: Input file not found: $input\n";
            exit(1);
        }

        self::getMedia()->processImage($input, $output, $width, $height, $quality, $fit, $rotate, $flip, $filter);
        echo "Image processed and saved to $output\n";
    }

    public static function handleBatchProcess(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:batch-process <input_dir> <output_dir> [w] [h] [q] [fit] [rot] [flip] [filter] [ext]\n";
            exit(1);
        }

        $inputDir = $argv[2];
        $outputDir = $argv[3];
        $width = isset($argv[4]) ? (int) $argv[4] : 0;
        $height = isset($argv[5]) ? (int) $argv[5] : 0;
        $quality = isset($argv[6]) ? (int) $argv[6] : 80;
        $fit = isset($argv[7]) ? $argv[7] : 'contain';
        $rotate = isset($argv[8]) ? (int) $argv[8] : 0;
        $flip = isset($argv[9]) ? $argv[9] : '';
        $filter = isset($argv[10]) ? $argv[10] : '';
        $ext = isset($argv[11]) ? $argv[11] : 'webp';

        if (!is_dir($inputDir)) {
            echo "Error: Input directory not found: $inputDir\n";
            exit(1);
        }

        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                echo "Error: Failed to create output directory: $outputDir\n";
                exit(1);
            }
        }

        $files = glob($inputDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
        if (empty($files)) {
            echo "No images found in $inputDir\n";
            return;
        }

        $count = 0;
        foreach ($files as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $outFile = $outputDir . '/' . $filename . '.' . $ext;

            echo "Processing: " . basename($file) . " -> " . basename($outFile) . "\n";
            self::getMedia()->processImage($file, $outFile, $width, $height, $quality, $fit, $rotate, $flip, $filter);
            $count++;
        }

        echo "Batch processing complete. Processed $count images.\n";
    }
}
