<?php

namespace GaiaAlpha;

class UiManager
{
    private static $components = [];

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

    public static function getComponents()
    {
        return self::$components;
    }
}
