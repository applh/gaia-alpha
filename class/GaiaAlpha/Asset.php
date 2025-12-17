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
                // Return minified URL with original query string if present
                // But wait, parse_url($path) handles the query string stripping?
                // Actually my observation was: Asset::url returns /min . $path. 
                // If $path has query string, it is preserved.
                // AssetController needs to handle it.
                return '/min' . $path;
            }
        }

        return $path;
    }
}
