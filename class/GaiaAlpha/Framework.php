<?php

namespace GaiaAlpha;

class Framework
{
    public static function loadControllers()
    {
        $rootDir = Env::get('root_dir');
        // Dynamically Init Controllers
        $controllers = [];
        foreach (glob($rootDir . '/class/GaiaAlpha/Controller/*Controller.php') as $file) {
            $filename = basename($file, '.php');
            if ($filename === 'BaseController')
                continue;

            $key = strtolower(str_replace('Controller', '', $filename));
            $className = "GaiaAlpha\\Controller\\$filename";

            if (class_exists($className)) {
                $controller = new $className();
                if (method_exists($controller, 'init')) {
                    $controller->init();
                }
                $controllers[$key] = $controller;
            }
        }

        Env::set('controllers', $controllers);
    }

    public static function registerRoutes()
    {
        $controllers = Env::get('controllers');
        foreach ($controllers as $controller) {
            $controller->registerRoutes();
        }
    }
}
