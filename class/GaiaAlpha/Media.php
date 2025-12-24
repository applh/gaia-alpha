<?php

namespace GaiaAlpha;

use GaiaAlpha\File;
class Media
{
    private string $uploadsDir;
    private string $cacheDir;

    public function __construct(string $dataDir)
    {
        $this->uploadsDir = $dataDir . '/uploads';
        $this->cacheDir = $dataDir . '/cache';

        File::makeDirectory($this->cacheDir);
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
        $mime = mime_content_type($path);
        if (!$mime) {
            $mime = 'application/octet-stream';
        }

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
            case 'image/avif':
                $image = imagecreatefromavif($src);
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
                imagewebp($output, $dst, $q);
                break;
            case 'avif':
                imageavif($output, $dst, $q);
                break;
            default:
                imagewebp($output, $dst, $q);
                break;
        }

        // Cleanup
        // imagedestroy($image); // Removed as it caused issues in PHP 8.5
        // imagedestroy($output);
    }

    public function makeTransparent(string $src, string $dst, array $color, int $fuzz = 0): void
    {
        $info = getimagesize($src);
        if (!$info)
            return;

        $mime = $info['mime'];
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
            case 'image/avif':
                $image = imagecreatefromavif($src);
                break;
            default:
                return;
        }

        if (!$image)
            return;

        // Convert to truecolor
        $w = imagesx($image);
        $h = imagesy($image);
        $output = imagecreatetruecolor($w, $h);

        // Preserve existing transparency
        imagealphablending($output, false);
        imagesavealpha($output, true);

        // Copy source
        imagecopy($output, $image, 0, 0, 0, 0, $w, $h);

        $targetR = $color[0];
        $targetG = $color[1];
        $targetB = $color[2];

        for ($y = 0; $y < $h; ++$y) {
            for ($x = 0; $x < $w; ++$x) {
                $rgb = imagecolorat($output, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $diff = sqrt(pow($r - $targetR, 2) + pow($g - $targetG, 2) + pow($b - $targetB, 2));

                if ($diff <= $fuzz) {
                    $alpha = 127; // Full transparency
                    $newColor = imagecolorallocatealpha($output, $r, $g, $b, $alpha);
                    imagesetpixel($output, $x, $y, $newColor);
                }
            }
        }

        $ext = strtolower(pathinfo($dst, PATHINFO_EXTENSION));
        if ($ext === 'png') {
            imagepng($output, $dst);
        } else {
            imagewebp($output, $dst);
        }

        // imagedestroy($image); // Deprecated in PHP 8.5
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

    public function upload(array $file, int $userId): array
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedTypes)) {
            throw new \Exception('Invalid file type. Allowed: JPG, PNG, WEBP, AVIF');
        }

        $userDir = $this->uploadsDir . '/' . $userId;
        File::makeDirectory($userDir);

        $useAvif = function_exists('imageavif');
        $ext = $useAvif ? '.avif' : '.webp';
        $filename = time() . '_' . bin2hex(random_bytes(4)) . $ext;
        $outputPath = $userDir . '/' . $filename;

        // Process and resize
        $this->processImage($file['tmp_name'], $outputPath, 3840, 2160, 80, 'contain');

        return [
            'filename' => $filename,
            'url' => '/media/' . $userId . '/' . $filename,
            'mime' => $mime
        ];
    }

    private function getDirStats(string $dir): array
    {
        $count = 0;
        $size = 0;

        if (!File::isDirectory($dir)) {
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
        if (!File::isDirectory($dir)) {
            return 0;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                File::delete($file->getRealPath());
                $count++;
            }
        }

        return $count;
    }
}
