<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Router;
use GaiaAlpha\Env;

class AssetController extends BaseController
{
    private $cacheDir;

    public function init()
    {
        $site = \GaiaAlpha\SiteManager::getCurrentSite() ?? 'default';
        $this->cacheDir = Env::get('path_data') . '/cache/min/' . $site;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
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
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        $rootDir = Env::get('root_dir');
        $sourceFile = $rootDir . '/resources/' . $type . '/' . $path;

        // Special handling for custom components moved to my-data
        if ($type === 'js' && strpos($path, 'components/custom/') === 0) {
            $customPath = Env::get('path_data') . '/' . $path;
            if (file_exists($customPath)) {
                $sourceFile = $customPath;
                goto found;
            }
        }

        if (!file_exists($sourceFile)) {
            // Fallback: Check if CSS is hidden in JS folder (common for vendor libs)
            if ($type === 'css') {
                $altSource = $rootDir . '/resources/js/' . $path;
                if (file_exists($altSource)) {
                    $sourceFile = $altSource;
                    goto found;
                }
            }

            // Fallback: Check for Plugin assets (plugins/PluginName/Path -> plugins/PluginName/resources/js/Path)
            if ($type === 'js' && strpos($path, 'plugins/') === 0) {
                // Remove 'plugins/' prefix
                $pluginPath = substr($path, 8);
                $parts = explode('/', $pluginPath);
                $pluginName = array_shift($parts);
                $rest = implode('/', $parts);

                $pluginSource = $rootDir . '/plugins/' . $pluginName . '/resources/js/' . $rest;
                if (file_exists($pluginSource)) {
                    $sourceFile = $pluginSource;
                    goto found;
                }
            }

            // Fallback: Check for Ace Editor files (ace-mode-*, ace-worker-*, ace-theme-*)
            // Ace requests 'mode-php.js', we have 'ace-mode-php.min.js'
            $filename = basename($path);
            if ($type === 'js' && (strpos($filename, 'mode-') === 0 || strpos($filename, 'worker-') === 0 || strpos($filename, 'theme-') === 0)) {
                $basename = basename($path, '.js'); // mode-php
                $aceName = 'ace-' . $basename . '.min.js'; // ace-mode-php.min.js

                // Check in vendor
                $aceSource = $rootDir . '/resources/js/vendor/' . $aceName;
                if (file_exists($aceSource)) {
                    $sourceFile = $aceSource;
                    goto found;
                }
            }


            http_response_code(404);
            echo "File not found";
            return;
        }

        found:

        // Detect if it's an image
        $ext = pathinfo($sourceFile, PATHINFO_EXTENSION);
        $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp']);

        // Check cache (skip for images? Or cache them too? 
        // Images are already "minified" binary, so we can just serve source or copy to cache.
        // Let's just serve source for images to save disk space and cpu, 
        // BUT we need to send correct headers.

        if ($isImage) {
            $content = file_get_contents($sourceFile);
            // Set Mime Type
            $mimeTypes = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp'
            ];
            $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
        } else {
            // Normal Minification Flow
            $cacheFile = $this->cacheDir . '/' . md5($path) . '.' . $type;
            $fileMtime = filemtime($sourceFile);

            // If cache exists and is fresh
            if (file_exists($cacheFile) && filemtime($cacheFile) >= $fileMtime) {
                $content = file_get_contents($cacheFile);
            } else {
                // Minify and cache
                $content = file_get_contents($sourceFile);
                if ($type === 'css') {
                    if (strpos($path, '.min.') === false && strpos($sourceFile, '.min.') === false) {
                        $content = $this->minifyCss($content);
                    }
                } else {
                    if (strpos($path, '.min.') === false && strpos($sourceFile, '.min.') === false) {
                        $content = $this->minifyJs($content);
                    }
                }
                file_put_contents($cacheFile, $content);
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
        while (ob_get_level()) {
            ob_end_clean();
        }

        header("Content-Type: $contentType", true);

        // Cache control (1 year)
        $seconds = 31536000;
        header("Cache-Control: public, max-age=$seconds");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($sourceFile)) . " GMT");

        echo $content;
        exit;
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
            http_response_code(403);
            echo "Forbidden";
            exit;
        }

        $rootDir = Env::get('root_dir');
        $sourceFile = $rootDir . '/resources/assets/' . $path;

        if (!file_exists($sourceFile)) {
            http_response_code(404);
            echo "File not found";
            exit;
        }

        $ext = pathinfo($sourceFile, PATHINFO_EXTENSION);
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf'
        ];

        $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

        // Clear any output buffers to ensure clean output
        while (ob_get_level()) {
            ob_end_clean();
        }

        header("Content-Type: $contentType", true);

        // Cache control (1 year)
        $seconds = 31536000;
        header("Cache-Control: public, max-age=$seconds");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($sourceFile)) . " GMT");

        readfile($sourceFile);
        exit;
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
}
