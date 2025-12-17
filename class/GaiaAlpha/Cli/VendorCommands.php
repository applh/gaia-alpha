<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\File;
use GaiaAlpha\Env;
use GaiaAlpha\Cli\Output;

class VendorCommands
{
    public static function handleUpdate(): void
    {
        Output::info("Updating vendor libraries...");
        $rootDir = Env::get('root_dir');
        $leafletDir = $rootDir . '/www/js/vendor/leaflet';
        $imagesDir = $leafletDir . '/images';

        File::makeDirectory($imagesDir);

        $files = [
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js' => $leafletDir . '/leaflet.js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' => $leafletDir . '/leaflet.css',
            'https://unpkg.com/leaflet@1.9.4/dist/images/layers.png' => $imagesDir . '/layers.png',
            'https://unpkg.com/leaflet@1.9.4/dist/images/layers-2x.png' => $imagesDir . '/layers-2x.png',
            'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png' => $imagesDir . '/marker-icon.png',
            'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png' => $imagesDir . '/marker-icon-2x.png',
            'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png' => $imagesDir . '/marker-shadow.png',
            'https://unpkg.com/vue@3.3.4/dist/vue.esm-browser.js' => $rootDir . '/resources/js/vendor/vue.esm-browser.js',
            'https://unpkg.com/globe.gl@2.28.0/dist/globe.gl.min.js' => $rootDir . '/resources/js/vendor/globe.gl.js',
            'https://unpkg.com/lucide@latest/dist/umd/lucide.min.js' => $rootDir . '/resources/js/vendor/lucide.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ace.min.js' => $rootDir . '/resources/js/vendor/ace.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/mode-php.min.js' => $rootDir . '/resources/js/vendor/ace-mode-php.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/theme-monokai.min.js' => $rootDir . '/resources/js/vendor/ace-theme-monokai.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/worker-php.min.js' => $rootDir . '/resources/js/vendor/ace-worker-php.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ext-language_tools.min.js' => $rootDir . '/resources/js/vendor/ace-ext-language_tools.min.js',
        ];

        foreach ($files as $url => $path) {
            Output::writeln("Downloading " . basename($path) . "...");
            $content = file_get_contents($url);
            if ($content === false) {
                Output::error("Failed to download $url");
                continue;
            }
            File::write($path, $content);
        }

        Output::success("Vendor update complete.");
    }
}
