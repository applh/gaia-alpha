<?php

namespace GaiaAlpha;

class Media
{
    private string $uploadsDir;
    private string $cacheDir;

    public function __construct(string $dataDir)
    {
        $this->uploadsDir = $dataDir . '/uploads';
        $this->cacheDir = $dataDir . '/cache';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function getUploadsDir(): string
    {
        return $this->uploadsDir;
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function serveFile(string $path): void
    {
        $mime = 'image/webp';
        header("Content-Type: $mime");
        header("Content-Length: " . filesize($path));
        header("Cache-Control: public, max-age=31536000, immutable"); // 1 year cache
        header("ETag: \"" . md5_file($path) . "\"");
        readfile($path);
        exit;
    }

    public function processImage(string $src, string $dst, int $w, int $h, int $q, string $fit, int $rotate = 0, string $flip = '', string $filter = ''): void
    {
        $info = getimagesize($src);
        if (!$info)
            return;

        $mime = $info['mime'];
        $srcW = $info[0];
        $srcH = $info[1];

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($src);
                break;
            case 'image/png':
                $image = imagecreatefrompng($src);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($src);
                break;
            default:
                return; // Unsupported
        }

        if (!$image)
            return;

        // Rotation
        if ($rotate != 0) {
            $rotated = imagerotate($image, -$rotate, 0);
            if ($rotated !== false) {
                // imagedestroy($image); // Deprecated in PHP 8.5
                $image = $rotated;
            }
        }

        // Flip
        if (!empty($flip)) {
            $mode = match ($flip) {
                'h', 'horizontal' => IMG_FLIP_HORIZONTAL,
                'v', 'vertical' => IMG_FLIP_VERTICAL,
                'b', 'both' => IMG_FLIP_BOTH,
                default => null
            };

            if ($mode !== null) {
                imageflip($image, $mode);
            }
        }

        // Update dimensions after transform
        $srcW = imagesx($image);
        $srcH = imagesy($image);

        // Calculate dimensions
        $dstW = $srcW;
        $dstH = $srcH;
        $srcX = 0;
        $srcY = 0;

        if ($w > 0 || $h > 0) {
            if ($fit === 'cover' && $w > 0 && $h > 0) {
                // Center crop
                $srcRatio = $srcW / $srcH;
                $dstRatio = $w / $h;

                if ($srcRatio > $dstRatio) {
                    // Image is wider than target
                    $tempH = $srcH;
                    $tempW = (int) ($srcH * $dstRatio);
                    $srcX = (int) (($srcW - $tempW) / 2);
                } else {
                    // Image is taller than target
                    $tempW = $srcW;
                    $tempH = (int) ($srcW / $dstRatio);
                    $srcY = (int) (($srcH - $tempH) / 2);
                }
                $srcW = $tempW;
                $srcH = $tempH;
                $dstW = $w;
                $dstH = $h;
            } else {
                // Contain / resize
                if ($w > 0 && $h > 0) {
                    $ratio = min($w / $srcW, $h / $srcH);
                } elseif ($w > 0) {
                    $ratio = $w / $srcW;
                } else {
                    $ratio = $h / $srcH;
                }

                $dstW = (int) ($srcW * $ratio);
                $dstH = (int) ($srcH * $ratio);
            }
        }

        $output = imagecreatetruecolor($dstW, $dstH);

        // Alpha handling
        imagealphablending($output, false);
        imagesavealpha($output, true);
        $transparent = imagecolorallocatealpha($output, 255, 255, 255, 127);
        imagefilledrectangle($output, 0, 0, $dstW, $dstH, $transparent);

        imagecopyresampled($output, $image, 0, 0, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);

        // Apply filters
        if (!empty($filter)) {
            $parts = explode(':', $filter);
            $filterName = $parts[0];
            $arg1 = $parts[1] ?? null;

            switch ($filterName) {
                case 'grayscale':
                    imagefilter($output, IMG_FILTER_GRAYSCALE);
                    break;
                case 'negate':
                    imagefilter($output, IMG_FILTER_NEGATE);
                    break;
                case 'edgedetect':
                    imagefilter($output, IMG_FILTER_EDGEDETECT);
                    break;
                case 'brightness':
                    if ($arg1 !== null) {
                        imagefilter($output, IMG_FILTER_BRIGHTNESS, (int) $arg1);
                    }
                    break;
                case 'contrast':
                    if ($arg1 !== null) {
                        imagefilter($output, IMG_FILTER_CONTRAST, (int) $arg1);
                    }
                    break;
                case 'pixelate':
                    if ($arg1 !== null) {
                        imagefilter($output, IMG_FILTER_PIXELATE, (int) $arg1, true);
                    }
                    break;
            }
        }

        // Determine output format based on extension
        $ext = strtolower(pathinfo($dst, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                // For JPEG, handle transparency by filling with white
                $bg = imagecreatetruecolor($dstW, $dstH);
                $white = imagecolorallocate($bg, 255, 255, 255);
                imagefilledrectangle($bg, 0, 0, $dstW, $dstH, $white);
                imagecopy($bg, $output, 0, 0, 0, 0, $dstW, $dstH);
                imagejpeg($bg, $dst, $q);
                // imagedestroy($bg); // Deprecated in PHP 8.5
                break;
            case 'png':
                // PNG quality is 0-9. Convert 0-100 to 0-9 roughly (invert)
                // 100 quality -> 0 compression. 0 quality -> 9 compression.
                $pngQuality = (int) (9 - round(($q / 100) * 9));
                imagepng($output, $dst, $pngQuality);
                break;
            case 'webp':
            default:
                imagewebp($output, $dst, $q);
                break;
        }

        // Cleanup
        // imagedestroy($image); // Removed as it caused issues in PHP 8.5
        // imagedestroy($output);
    }

    public function getStats(): array
    {
        $uploadStats = $this->getDirStats($this->uploadsDir);
        $cacheStats = $this->getDirStats($this->cacheDir);

        return [
            'uploads' => $uploadStats,
            'cache' => $cacheStats
        ];
    }

    public function clearCache(): int
    {
        return $this->deleteDirContents($this->cacheDir);
    }

    private function getDirStats(string $dir): array
    {
        $count = 0;
        $size = 0;

        if (!is_dir($dir)) {
            return ['count' => 0, 'size' => 0];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $count++;
            $size += $file->getSize();
        }

        return ['count' => $count, 'size' => $size];
    }

    private function deleteDirContents(string $dir): int
    {
        $count = 0;
        if (!is_dir($dir)) {
            return 0;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                unlink($file->getRealPath());
                $count++;
            }
        }

        return $count;
    }
}
