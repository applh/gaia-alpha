<?php
namespace GaiaAlpha\Helper;

use GaiaAlpha\Controller\DbController;
use GaiaAlpha\Env;

class Part
{
    /**
     * Load and render a partial.
     * 
     * @param string $name The name of the partial (slug)
     * @param array $data Optional data to extract into the partial's scope
     */
    public static function load($name, $data = [])
    {
        // Check Cache
        $cacheDir = Env::get('path_data') . '/cache/partials';
        $cacheFile = $cacheDir . '/' . $name . '.php';

        if (!\GaiaAlpha\File::exists($cacheFile)) {
            // Fetch from DB
            $content = \GaiaAlpha\Model\DB::fetchColumn("SELECT content FROM cms_partials WHERE name = ?", [$name]);

            if ($content === false) {
                echo "<!-- Partial '$name' not found -->";
                return;
            }

            \GaiaAlpha\File::makeDirectory($cacheDir);
            \GaiaAlpha\File::write($cacheFile, $content);
        }

        // Make $page available if it exists in the global scope? 
        // Or if it was passed in $data?
        // Standard practice: if $page is usually available, we might want to capture it.
        // But 'global $page' only works if $page is truly global. 
        // In PublicController, it's local. 
        // Effectively, users should pass specific data: Part::load('header', ['page' => $page]);
        // However, to be "simpler", we can try to look at backtrace? No.

        extract($data);
        require $cacheFile;
    }

    /**
     * Alias for load().
     * Usage: Part::in('name');
     */
    public static function in($name, $data = [])
    {
        return self::load($name, $data);
    }
}
