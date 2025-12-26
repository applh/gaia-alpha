<?php

namespace GaiaAlpha;
use GaiaAlpha\Env;
use GaiaAlpha\Debug;
use GaiaAlpha\File;

class Framework
{
    public static function loadPlugins()
    {
        $rootDir = Env::get('root_dir');
        $pathData = Env::get('path_data');
        $manifestFile = $pathData . '/cache/plugins_manifest.json';
        $currentContext = Request::context();

        // Try to load from manifest first
        if (!isset($_GET['clear_cache']) && file_exists($manifestFile)) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
            if (is_array($manifest)) {
                foreach ($manifest as $pluginData) {
                    $pluginPath = is_array($pluginData) ? $pluginData['path'] : $pluginData;
                    $pluginContext = is_array($pluginData) ? ($pluginData['context'] ?? 'all') : 'all';

                    // Register menu items if available (from cache)
                    if (is_array($pluginData) && isset($pluginData['menu'])) {
                        // Inherit plugin dir name from path or store it?
                        // Path is absolute, so basename(dirname($pluginPath)) gives the dir name.
                        self::registerPluginMenuItems($pluginData['menu'], basename(dirname($pluginPath)));
                    }

                    if ($currentContext === 'install' && $pluginContext !== 'install') {
                        continue;
                    }

                    if ($pluginContext !== 'all' && $pluginContext !== $currentContext) {
                        continue;
                    }

                    if (file_exists($pluginPath)) {
                        include_once $pluginPath;
                    }
                }
                Hook::run('plugins_loaded');
                return;
            }
        }

        $pluginDirs = [
            $pathData . '/plugins',
            $rootDir . '/plugins'
        ];

        $activePluginsFile = $pathData . '/active_plugins.json';
        $activePlugins = [];
        if (file_exists($activePluginsFile)) {
            $activePlugins = json_decode(file_get_contents($activePluginsFile), true) ?: [];
        }

        $manifest = [];
        foreach ($pluginDirs as $pluginsDir) {
            if (!is_dir($pluginsDir)) {
                continue;
            }

            foreach (glob($pluginsDir . '/*/index.php') as $plugin) {
                $pluginDir = dirname($plugin);
                $pluginDirName = basename($pluginDir);

                if (!in_array($pluginDirName, $activePlugins)) {
                    continue;
                }

                $pluginContext = 'all';

                // Read config for context and declarative menu config
                $configFile = $pluginDir . '/plugin.json';
                if (file_exists($configFile)) {
                    $config = json_decode(file_get_contents($configFile), true);
                    if (is_array($config)) {
                        $pluginContext = $config['context'] ?? 'all';
                        if (isset($config['menu'])) {
                            self::registerPluginMenuItems($config['menu'], $pluginDirName);
                        }
                    }
                }

                $manifest[] = [
                    'path' => $plugin,
                    'context' => $pluginContext,
                    'menu' => $config['menu'] ?? null
                ];

                if ($pluginContext === 'all' || $pluginContext === $currentContext) {
                    include_once $plugin;
                }
            }
        }

        // Save manifest
        File::makeDirectory(dirname($manifestFile));
        File::writeJson($manifestFile, $manifest, 0);

        Hook::run('plugins_loaded');
    }

    public static function registerInstallController()
    {
        $className = 'GaiaAlpha\\Controller\\InstallController';
        if (class_exists($className)) {
            $controller = new $className();
            // No init needed
            Env::add('controllers', $controller, 'install');
        }
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
                    if (!isset($data['user']['menu_items'])) {
                        $data['user']['menu_items'] = [];
                    }

                    $foundGroup = false;
                    foreach ($data['user']['menu_items'] as &$existingItem) {
                        if (isset($existingItem['id']) && $existingItem['id'] === $item['group']) {
                            if (!isset($existingItem['children'])) {
                                $existingItem['children'] = [];
                            }
                            $existingItem['children'][] = $menuItem;
                            $foundGroup = true;
                            break;
                        }
                    }

                    if (!$foundGroup) {
                        $data['user']['menu_items'][] = [
                            'id' => $item['group'],
                            // Try to infer label/icon for standard groups, or default
                            'label' => ucfirst(str_replace('grp-', '', $item['group'])),
                            'icon' => 'folder',
                            'children' => [$menuItem]
                        ];
                    }
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
        $pathData = Env::get('path_data');
        $manifestFile = $pathData . '/cache/controllers_manifest.json';

        $controllers = Env::get('controllers') ?: [];

        if (!isset($_GET['clear_cache']) && file_exists($manifestFile)) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
            if (is_array($manifest)) {
                foreach ($manifest as $key => $className) {
                    Hook::run('app_task_before', "load_ctrl_$key", "Load $className");
                    if (class_exists($className)) {
                        $controller = new $className();
                        if (method_exists($controller, 'init')) {
                            $controller->init();
                        }
                        Hook::run('controller_init', $controller, $key);
                        $controllers[$key] = $controller;
                    }
                    Hook::run('app_task_after', "load_ctrl_$key", "Load $className");
                }
                Env::set('controllers', $controllers);
                Hook::run('framework_load_controllers_after', $controllers);
                return;
            }
        }

        // Dynamically Init Controllers
        $manifest = [];
        foreach (glob($rootDir . '/class/GaiaAlpha/Controller/*Controller.php') as $file) {
            $filename = basename($file, '.php');
            if ($filename === 'BaseController')
                continue;

            $key = strtolower(str_replace('Controller', '', $filename));
            $className = "GaiaAlpha\\Controller\\$filename";

            Hook::run('app_task_before', "load_ctrl_$key", "Load $filename");

            if (class_exists($className)) {
                $controller = new $className();
                if (method_exists($controller, 'init')) {
                    $controller->init();
                }

                // Hook for controller initialization
                Hook::run('controller_init', $controller, $key);

                $controllers[$key] = $controller;
                $manifest[$key] = $className;
            }

            Hook::run('app_task_after', "load_ctrl_$key", "Load $filename");
        }

        // Save manifest
        File::makeDirectory(dirname($manifestFile));
        File::writeJson($manifestFile, $manifest, 0);

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
            if (method_exists($controller, 'registerRoutes')) {
                $controller->registerRoutes();
            }
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

            // Note: registerRoutes is called globally in Framework::registerRoutes()
            // precisely to allow sorting and control over order.

            Env::add('controllers', $controller, $key);
        }
    }
}
