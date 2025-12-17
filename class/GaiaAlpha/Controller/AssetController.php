<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Router;
use GaiaAlpha\Env;
use GaiaAlpha\File;
use GaiaAlpha\Response;

class AssetController extends BaseController
{
    private $cacheDir;

    public function init()
    {
        $site = \GaiaAlpha\SiteManager::getCurrentSite() ?? 'default';
        $this->cacheDir = Env::get('path_data') . '/cache/min/' . $site;

        File::makeDirectory($this->cacheDir);
    }

    public function serveCss($path)
    {
        $this->serveAsset($path, 'css');
    }

    public function serveJs($path)
    {
        $this->serveAsset($path, 'js');
    }

    private function serveAsset($path, $type)
    {
        // Strip query string if present
        if (strpos($path, '?') !== false) {
            $path = explode('?', $path)[0];
        }

        // Security check: prevent directory traversal
        if (strpos($path, '..') !== false) {
            Response::send("Forbidden", 403);
            return;
        }

        $sourceFile = $this->findAssetPath($path, $type);

        if (!$sourceFile) {
            Response::send("File not found", 404);
            return;
        }

        // Detect if it's an image
        $ext = pathinfo($sourceFile, PATHINFO_EXTENSION);
        $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp']);

        // Check cache (skip for images? Or cache them too? 
        // Images are already "minified" binary, so we can just serve source or copy to cache.
        // Let's just serve source for images to save disk space and cpu, 
        // BUT we need to send correct headers.

        if ($isImage) {
            $content = File::read($sourceFile);
            $contentType = File::mimeType($sourceFile);
        } else {
            // Normal Minification Flow
            $cacheFile = $this->cacheDir . '/' . md5($path) . '.' . $type;
            $fileMtime = filemtime($sourceFile);

            // If cache exists and is fresh
            if (File::exists($cacheFile) && filemtime($cacheFile) >= $fileMtime) {
                $content = File::read($cacheFile);
            } else {
                // Minify and cache
                $content = File::read($sourceFile);
                if ($type === 'css') {
                    if (strpos($path, '.min.') === false && strpos($sourceFile, '.min.') === false) {
                        $content = $this->minifyCss($content);
                    }
                } else {
                    if (strpos($path, '.min.') === false && strpos($sourceFile, '.min.') === false) {
                        $content = $this->minifyJs($content);
                    }
                }
                File::write($cacheFile, $content);
            }

            // Determine Content-Type
            if ($type === 'css') {
                $contentType = 'text/css';
            } else {
                $contentType = 'application/javascript';
            }
        }

        // Set Headers
        // Clear any output buffers to ensure clean output
        Response::clearBuffer();
        Response::header("Content-Type: $contentType", true);

        // Cache control (1 year)
        $seconds = 31536000;
        Response::header("Cache-Control: public, max-age=$seconds");
        Response::header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($sourceFile)) . " GMT");

        Response::send($content, 200, true);
    }

    private function minifyCss($input)
    {
        // Remove comments
        $input = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $input);
        // Remove space after colons
        $input = str_replace(': ', ':', $input);
        // Remove whitespace
        $input = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $input);
        return $input;
    }

    private function minifyJs($input)
    {
        // Simple JS Minifier
        // 1. Remove Multi-line comments
        $input = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $input);

        // 2. Remove Single-line comments (risky if not careful, so basic check)
        // This is a naive approach, might break URLs or RegEx. 
        // Safer: match strings/regex first, then comments.
        // For now, let's keep it safe: just trim lines and remove empty lines.

        $lines = explode("\n", $input);
        $output = [];
        foreach ($lines as $line) {
            $line = trim($line);
            // Basic comment removal if line starts with //
            if (empty($line) || strpos($line, '//') === 0)
                continue;

            $output[] = $line;
        }

        return implode("\n", $output);
    }

    public function servePublic($path)
    {
        // Security check: prevent directory traversal
        if (strpos($path, '..') !== false) {
            Response::send("Forbidden", 403);
            exit;
        }

        $rootDir = Env::get('root_dir');
        $sourceFile = $rootDir . '/resources/assets/' . $path;

        if (!File::exists($sourceFile)) {
            Response::send("File not found", 404);
            exit;
        }

        $contentType = File::mimeType($sourceFile);

        // Clear any output buffers to ensure clean output
        Response::clearBuffer();
        Response::header("Content-Type: $contentType", true);

        // Cache control (1 year)
        $seconds = 31536000;
        Response::header("Cache-Control: public, max-age=$seconds");
        Response::header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($sourceFile)) . " GMT");

        Response::file($sourceFile, true);
    }

    public function registerRoutes()
    {
        // Matches /min/css/site.css
        Router::get('/min/css/(.+)', [$this, 'serveCss']);

        // Matches /min/js/vendor/(.+) -> serveJs('vendor/$1')
        Router::get('/min/js/vendor/(.+)', function ($file) {
            $this->serveJs('vendor/' . $file);
        });

        // Matches /min/js/(.+)
        Router::get('/min/js/(.+)', [$this, 'serveJs']);

        // Matches /assets/... for static resources
        Router::get('/assets/(.+)', [$this, 'servePublic']);
    }
    private function findAssetPath($path, $type)
    {
        $rootDir = Env::get('root_dir');

        // 1. Special handling for custom components moved to my-data
        if ($type === 'js' && strpos($path, 'components/custom/') === 0) {
            $customPath = Env::get('path_data') . '/' . $path;
            if (File::exists($customPath)) {
                return $customPath;
            }
        }

        // 2. Standard Resource Path
        $sourceFile = $rootDir . '/resources/' . $type . '/' . $path;
        if (File::exists($sourceFile)) {
            return $sourceFile;
        }

        // 3. Fallback: Check if CSS is hidden in JS folder (common for vendor libs)
        if ($type === 'css') {
            $altSource = $rootDir . '/resources/js/' . $path;
            if (File::exists($altSource)) {
                return $altSource;
            }
        }

        // 4. Fallback: Check for Plugin assets
        if ($type === 'js' && strpos($path, 'plugins/') === 0) {
            $pluginPath = substr($path, 8);
            $parts = explode('/', $pluginPath);
            $pluginName = array_shift($parts);
            $rest = implode('/', $parts);

            $pluginSource = $rootDir . '/plugins/' . $pluginName . '/resources/js/' . $rest;
            if (File::exists($pluginSource)) {
                return $pluginSource;
            }
        }

        // 5. Fallback: Check for Ace Editor files
        $filename = basename($path);
        if ($type === 'js' && (strpos($filename, 'mode-') === 0 || strpos($filename, 'worker-') === 0 || strpos($filename, 'theme-') === 0)) {
            $basename = basename($path, '.js');
            $aceName = 'ace-' . $basename . '.min.js';
            $aceSource = $rootDir . '/resources/js/vendor/' . $aceName;
            if (File::exists($aceSource)) {
                return $aceSource;
            }
        }

        return null;
    }
}
