<?php

namespace GaiaAlpha;

class Asset
{
    public static function url($path)
    {
        // Check if minification is enabled (default true)
        // In the future we can make this configurable via Env::get('minify_assets', true)
        $minify = true;

        if ($minify) {
            // Parse path to remove query string
            $cleanPath = parse_url($path, PHP_URL_PATH);
            $ext = pathinfo($cleanPath, PATHINFO_EXTENSION);
            if (in_array($ext, ['css', 'js'])) {
                // Return minified URL
                return '/min' . $path;
            }
        }

        return $path;
    }
}
