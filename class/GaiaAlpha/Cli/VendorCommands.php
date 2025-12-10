<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Env;

class VendorCommands
{
    public static function handleUpdate(): void
    {
        echo "Updating vendor libraries...\n";
        $rootDir = Env::get('root_dir');
        $leafletDir = $rootDir . '/www/js/vendor/leaflet';
        $imagesDir = $leafletDir . '/images';

        if (!is_dir($imagesDir)) {
            mkdir($imagesDir, 0755, true);
        }

        $files = [
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js' => $leafletDir . '/leaflet.js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' => $leafletDir . '/leaflet.css',
            'https://unpkg.com/leaflet@1.9.4/dist/images/layers.png' => $imagesDir . '/layers.png',
            'https://unpkg.com/leaflet@1.9.4/dist/images/layers-2x.png' => $imagesDir . '/layers-2x.png',
            'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png' => $imagesDir . '/marker-icon.png',
            'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png' => $imagesDir . '/marker-icon-2x.png',
            'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png' => $imagesDir . '/marker-shadow.png',
            'https://unpkg.com/vue@3.3.4/dist/vue.esm-browser.js' => $rootDir . '/www/js/vendor/vue.esm-browser.js',
            'https://unpkg.com/globe.gl@2.28.0/dist/globe.gl.min.js' => $rootDir . '/www/js/vendor/globe.gl.js',
        ];

        foreach ($files as $url => $path) {
            echo "Downloading " . basename($path) . "...\n";
            $content = file_get_contents($url);
            if ($content === false) {
                echo "Error downloading $url\n";
                continue;
            }
            file_put_contents($path, $content);
        }

        echo "Vendor update complete.\n";
    }
}
