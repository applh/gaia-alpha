<?php

namespace GaiaAlpha;
use GaiaAlpha\Env;
use GaiaAlpha\Debug;

class Framework
{
    public static function loadPlugins()
    {
        $rootDir = Env::get('root_dir');
        $pathData = Env::get('path_data');

        $pluginDirs = [
            $pathData . '/plugins',
            $rootDir . '/plugins'
        ];

        $activePluginsFile = $pathData . '/active_plugins.json';
        $activePlugins = null;
        if (file_exists($activePluginsFile)) {
            $activePlugins = json_decode(file_get_contents($activePluginsFile), true);
        }

        foreach ($pluginDirs as $pluginsDir) {
            if (!is_dir($pluginsDir)) {
                continue;
            }

            foreach (glob($pluginsDir . '/*/index.php') as $plugin) {
                $pluginDirName = basename(dirname($plugin));

                // If active_plugins.json exists, only load if in list
                if ($activePlugins !== null && !in_array($pluginDirName, $activePlugins)) {
                    continue;
                }

                include_once $plugin;
            }
        }

        Hook::run('plugins_loaded');
    }

    public static function appBoot()
    {
        // Debug hooking
        Hook::add('database_query_executed', function ($sql, $params, $duration) {
            Debug::logQuery($sql, $params, $duration);
        });

        Hook::run('app_boot');
    }

    public static function loadControllers()
    {
        Hook::run('framework_load_controllers_before');

        $rootDir = Env::get('root_dir');
        // Dynamically Init Controllers
        $controllers = [];
        foreach (glob($rootDir . '/class/GaiaAlpha/Controller/*Controller.php') as $file) {
            $filename = basename($file, '.php');
            if ($filename === 'BaseController')
                continue;

            $key = strtolower(str_replace('Controller', '', $filename));

            Debug::startTask("load_ctrl_$key", "Load $filename");

            $className = "GaiaAlpha\\Controller\\$filename";

            if (class_exists($className)) {
                $controller = new $className();
                if (method_exists($controller, 'init')) {
                    $controller->init();
                }

                // Hook for controller initialization
                Hook::run('controller_init', $controller, $key);

                $controllers[$key] = $controller;
            }

            Debug::endTask("load_ctrl_$key", "Load $filename");
        }

        Env::set('controllers', $controllers);
        Hook::run('framework_load_controllers_after', $controllers);
    }

    public static function sortControllers()
    {
        $controllers = Env::get('controllers');

        uasort($controllers, function ($a, $b) {
            $rankA = method_exists($a, 'getRank') ? $a->getRank() : 10;
            $rankB = method_exists($b, 'getRank') ? $b->getRank() : 10;

            if ($rankA === $rankB) {
                return 0;
            }
            return ($rankA < $rankB) ? -1 : 1;
        });

        Env::set('controllers', $controllers);
    }

    public static function registerRoutes()
    {
        $controllers = Env::get('controllers');
        foreach ($controllers as $controller) {
            $controller->registerRoutes();
        }
    }
    public static function checkAuth($level = 0)
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        // Check if user is logged in (user_id exists) and has sufficient level
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['level']) || $_SESSION['level'] < $level) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        return true;
    }

    public static function json($data, $status = 200)
    {
        return Response::json($data, $status);
    }

    public static function decodeBody()
    {
        return Request::input();
    }
}
