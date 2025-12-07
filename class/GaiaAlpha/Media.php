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

    public function handleRequest(string $userId, string $filename, array $params): void
    {
        // Sanitize path to prevent directory traversal
        $filename = basename($filename);
        $userId = (int) $userId;

        $sourcePath = $this->uploadsDir . '/' . $userId . '/' . $filename;

        if (!file_exists($sourcePath)) {
            http_response_code(404);
            header("Content-Type: text/plain");
            echo "File not found";
            return;
        }

        // Cache Key Logic
        $width = isset($params['w']) ? (int) $params['w'] : 0;
        $height = isset($params['h']) ? (int) $params['h'] : 0;
        $quality = isset($params['q']) ? (int) $params['q'] : 80;
        $fit = isset($params['fit']) ? $params['fit'] : 'contain'; // contain, cover

        $cacheKey = md5($userId . '_' . $filename . '_' . $width . '_' . $height . '_' . $quality . '_' . $fit);
        $cachePath = $this->cacheDir . '/' . $cacheKey . '.webp'; // We convert everything to webp

        // Serve from cache if exists
        if (file_exists($cachePath) && filemtime($cachePath) >= filemtime($sourcePath)) {
            $this->serveRaw($cachePath);
            return;
        }

        // Process Image
        $this->processImage($sourcePath, $cachePath, $width, $height, $quality, $fit);

        if (file_exists($cachePath)) {
            $this->serveRaw($cachePath);
        } else {
            http_response_code(500);
            echo "Image processing failed";
        }
    }

    private function serveRaw(string $path): void
    {
        $mime = 'image/webp';
        header("Content-Type: $mime");
        header("Content-Length: " . filesize($path));
        header("Cache-Control: public, max-age=31536000, immutable"); // 1 year cache
        header("ETag: \"" . md5_file($path) . "\"");
        readfile($path);
        exit;
    }

    private function processImage(string $src, string $dst, int $w, int $h, int $q, string $fit): void
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

        imagewebp($output, $dst, $q);

        // Cleanup
        // imagedestroy($image); // Removed as it caused issues in PHP 8.5
        // imagedestroy($output);
    }
}
