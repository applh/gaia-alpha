<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Env;
use GaiaAlpha\Media;
use GaiaAlpha\Router;

class MediaController extends BaseController
{
    private Media $media;

    public function init()
    {
        $this->media = new Media(Env::get('path_data'));
    }

    public function serve($userId, $filename)
    {
        $params = $_GET; // Using superglobal as in original usage context (App.php passed $_GET)

        // Sanitize path to prevent directory traversal
        $filename = basename($filename);
        $userId = (int) $userId;

        $sourcePath = $this->media->getUploadsDir() . '/' . $userId . '/' . $filename;

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

        $useAvif = function_exists('imageavif');
        $ext = $useAvif ? '.avif' : '.webp';

        $cacheKey = md5($userId . '_' . $filename . '_' . $width . '_' . $height . '_' . $quality . '_' . $fit);
        $cachePath = $this->media->getCacheDir() . '/' . $cacheKey . $ext;

        // Serve from cache if exists
        if (file_exists($cachePath) && filemtime($cachePath) >= filemtime($sourcePath)) {
            $this->media->serveFile($cachePath);
            return;
        }

        // Process Image
        $this->media->processImage($sourcePath, $cachePath, $width, $height, $quality, $fit);

        if (file_exists($cachePath)) {
            $this->media->serveFile($cachePath);
        } else {
            http_response_code(500);
            echo "Image processing failed";
        }
    }

    public function stats()
    {
        $this->requireAuth();
        $dir = $this->media->getCacheDir();
        $files = glob($dir . '/*');
        $count = count($files);
        $size = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            }
        }

        $this->jsonResponse([
            'count' => $count,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size)
        ]);
    }

    public function clear()
    {
        $this->requireAuth();
        $dir = $this->media->getCacheDir();
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->jsonResponse(['success' => true, 'message' => 'Cache cleared']);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function registerRoutes()
    {
        Router::add('GET', '/media/(\d+)/(.+)', [$this, 'serve']);
        Router::add('GET', '/@/media/cache', [$this, 'stats']);
        Router::add('POST', '/@/media/cache/clear', [$this, 'clear']);
    }
}
