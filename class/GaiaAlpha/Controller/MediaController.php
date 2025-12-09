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

        $cacheKey = md5($userId . '_' . $filename . '_' . $width . '_' . $height . '_' . $quality . '_' . $fit);
        $cachePath = $this->media->getCacheDir() . '/' . $cacheKey . '.webp'; // We convert everything to webp

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

    public function registerRoutes(Router $router)
    {
        $router->add('GET', '/media/(\d+)/(.+)', [$this, 'serve']);
    }
}
