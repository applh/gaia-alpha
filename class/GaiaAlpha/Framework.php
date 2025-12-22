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
                $pluginDir = dirname($plugin);
                $pluginDirName = basename($pluginDir);

                // Read config to check for type="core"
                $configFile = $pluginDir . '/plugin.json';
                $config = [];
                if (file_exists($configFile)) {
                    $config = json_decode(file_get_contents($configFile), true);
                }

                // If active_plugins.json exists, ONLY whitelist if NOT core
                $isCore = isset($config['type']) && $config['type'] === 'core';

                if ($activePlugins !== null && !in_array($pluginDirName, $activePlugins)) {
                    continue;
                }

                // Check for declarative menu config
                if (is_array($config) && isset($config['menu'])) {
                    self::registerPluginMenuItems($config['menu'], $pluginDirName);
                }

                include_once $plugin;
            }
        }

        Hook::run('plugins_loaded');
    }

    /**
     * Register menu items from plugin.json configuration
     */
    private static function registerPluginMenuItems($menuConfig, $pluginName)
    {
        if (!isset($menuConfig['items']) || !is_array($menuConfig['items'])) {
            return;
        }

        $priority = $menuConfig['priority'] ?? 10;

        Hook::add('auth_session_data', function ($data) use ($menuConfig) {
            foreach ($menuConfig['items'] as $item) {
                // Check admin-only permission
                if (isset($item['adminOnly']) && $item['adminOnly']) {
                    if (!isset($data['user']) || $data['user']['level'] < 100) {
                        continue;
                    }
                }

                // Build menu item
                $menuItem = [
                    'label' => $item['label'],
                    'view' => $item['view'] ?? null,
                    'icon' => $item['icon'] ?? 'circle'
                ];

                // Add to group or create new group
                if (isset($item['group'])) {
                    $data['user']['menu_items'][] = [
                        'id' => $item['group'],
                        'children' => [$menuItem]
                    ];
                } else {
                    $data['user']['menu_items'][] = $menuItem;
                }
            }
            return $data;
        }, $priority);
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

    /**
     * Centralized method to register a controller, typically called from plugin index.php
     * 
     * @param string $key Unique key for the controller (e.g. 'analytics')
     * @param string|object $controller Class name or instance of the controller
     */
    public static function registerController(string $key, $controller)
    {
        if (is_string($controller) && class_exists($controller)) {
            $controller = new $controller();
        }

        if (is_object($controller)) {
            if (method_exists($controller, 'init')) {
                $controller->init();
            }

            if (method_exists($controller, 'registerRoutes')) {
                $controller->registerRoutes();
            }

            Env::add('controllers', $controller, $key);
        }
    }
}
