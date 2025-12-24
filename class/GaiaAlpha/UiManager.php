<?php

namespace GaiaAlpha;

class UiManager
{
    private static $components = [];
    private static $styles = [];

    /**
     * Register a frontend component to be dynamically loaded.
     *
     * @param string $viewName The view identifier (e.g., 'ml-pca')
     * @param string $importPath The ES module import path (e.g., 'plugins/MlPca/MlPca.js')
     * @param bool $adminOnly Whether the view is restricted to admins
     */
    public static function registerComponent(string $viewName, string $importPath, bool $adminOnly = false)
    {
        self::$components[$viewName] = [
            'path' => $importPath,
            'adminOnly' => $adminOnly
        ];
    }

    /**
     * Register a CSS file to be loaded.
     *
     * @param string $id Unique identifier
     * @param string $path Path to CSS file
     */
    public static function registerStyle(string $id, string $path)
    {
        self::$styles[$id] = $path;
    }

    public static function getComponents()
    {
        return self::$components;
    }

    public static function getStyles()
    {
        return self::$styles;
    }
}
