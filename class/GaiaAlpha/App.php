<?php

namespace GaiaAlpha;

use GaiaAlpha\Controller\DbController;

class App
{
    public static function run()
    {
        register_shutdown_function(function () {
            Hook::run('app_terminate');
        });

        foreach (Env::get('framework_tasks') as $step => $task) {
            if (is_callable($task)) {
                $taskName = '';
                if (is_string($task)) {
                    $taskName = $task;
                } elseif (is_array($task)) {
                    $class = is_object($task[0]) ? get_class($task[0]) : $task[0];
                    $taskName = $class . '::' . $task[1];
                }

                // Sanitize task name for hook
                $hookSuffix = str_replace(['\\', '::'], '_', $taskName);

                // Generic hook
                Hook::run('app_task_before', $step, $task);
                // Step-based hook
                Hook::run("app_task_before_{$step}", $task);
                // Name-based hook
                if ($hookSuffix) {
                    Hook::run("app_task_before_{$hookSuffix}", $step);
                }

                $task();

                // Name-based hook
                if ($hookSuffix) {
                    Hook::run("app_task_after_{$hookSuffix}", $step);
                }
                // Step-based hook
                Hook::run("app_task_after_{$step}", $task);
                // Generic hook
                Hook::run('app_task_after', $step, $task);
            }
        }
    }

    public static function web_setup(string $rootDir)
    {
        Hook::run('app_init');
        Env::set('root_dir', $rootDir);

        // 1. Determine Data Path
        if (getenv('GAIA_DATA_PATH')) {
            if (!defined('GAIA_DATA_PATH')) {
                define('GAIA_DATA_PATH', getenv('GAIA_DATA_PATH'));
            }
        }

        if (file_exists($rootDir . '/loader.php')) {
            require_once $rootDir . '/loader.php';
        }

        if (!defined('GAIA_DATA_PATH')) {
            define('GAIA_DATA_PATH', $rootDir . '/my-data');
        }

        $dataPath = GAIA_DATA_PATH;
        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0755, true);
        }
        Env::set('path_data', $dataPath);

        // 2. Load Config
        if (file_exists($dataPath . '/config.php')) {
            require_once $dataPath . '/config.php';
        } elseif (file_exists($rootDir . '/my-config.php')) {
            require_once $rootDir . '/my-config.php';
        }

        // Resolve Site / DB Path
        \GaiaAlpha\SiteManager::resolve();

        // Load Global Configuration for Admin Slug
        if (class_exists('\\GaiaAlpha\\Model\\DataStore')) {
            $adminSlug = \GaiaAlpha\Model\DataStore::get(0, 'global_config', 'admin_slug');
            if ($adminSlug) {
                Env::set('admin_prefixes', ['/@/' . $adminSlug]);
            }
        }

        Env::set('controllers', []);
        Env::set('framework_tasks', [
            "step00" => "GaiaAlpha\\Debug::init",
            "step01" => "GaiaAlpha\\Response::startBuffer",
            "step05" => "GaiaAlpha\\Framework::loadPlugins",
            "step06" => "GaiaAlpha\\Framework::appBoot",
            "step10" => "GaiaAlpha\\Framework::loadControllers",
            "step12" => "GaiaAlpha\\Framework::sortControllers",
            "step15" => "GaiaAlpha\\Framework::registerRoutes",
            "step18" => "GaiaAlpha\\Controller\\InstallController::checkInstalled",
            "step20" => "GaiaAlpha\\Router::handle",
            "step99" => "GaiaAlpha\\Response::flush",
        ]);

        self::registerAutoloaders();
    }

    public static function cli_setup(string $rootDir)
    {
        if (php_sapi_name() !== 'cli') {
            die("This script must be run from the command line.\n");
        }

        Hook::run('app_init');
        Env::set('root_dir', $rootDir);

        // 1. Determine Data Path
        if (getenv('GAIA_DATA_PATH')) {
            if (!defined('GAIA_DATA_PATH')) {
                define('GAIA_DATA_PATH', getenv('GAIA_DATA_PATH'));
            }
        }

        if (file_exists($rootDir . '/loader.php')) {
            require_once $rootDir . '/loader.php';
        }

        if (!defined('GAIA_DATA_PATH')) {
            define('GAIA_DATA_PATH', $rootDir . '/my-data');
        }

        $dataPath = GAIA_DATA_PATH;
        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0755, true);
        }
        Env::set('path_data', $dataPath);

        // 2. Load Config
        if (file_exists($dataPath . '/config.php')) {
            require_once $dataPath . '/config.php';
        } elseif (file_exists($rootDir . '/my-config.php')) {
            require_once $rootDir . '/my-config.php';
        }

        // Resolve Site / DB Path
        \GaiaAlpha\SiteManager::resolve();

        Env::set('framework_tasks', [
            "step00" => "GaiaAlpha\\Debug::init",
            "step05" => "GaiaAlpha\\Framework::loadPlugins",
            "step06" => "GaiaAlpha\\Framework::appBoot",
            "step10" => "GaiaAlpha\\Cli::run"
        ]);

        self::registerAutoloaders();
    }

    public static function registerAutoloaders()
    {
        // 1. Core Framework Autoloader
        spl_autoload_register(function ($class) {
            // Check if class starts with GaiaAlpha\
            if (strpos($class, 'GaiaAlpha\\') === 0) {
                // Class: GaiaAlpha\Sub\Foo
                // File: .../class/GaiaAlpha/Sub/Foo.php
                // __DIR__: .../class/GaiaAlpha

                // Remove prefix 'GaiaAlpha\' (length 10)
                $relative = substr($class, 10);

                // Construct path inside __DIR__
                $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

                if (file_exists($file)) {
                    require $file;
                }
            }
        });

        // 2. Plugins Autoloader
        spl_autoload_register(function ($class) {
            // Top-level namespace is the plugin name
            $parts = explode('\\', $class);
            $pluginName = array_shift($parts);

            if (empty($parts)) {
                return;
            }

            // Locations to search: data_path and root_dir
            $roots = [
                Env::get('path_data') . '/plugins',
                Env::get('root_dir') . '/plugins'
            ];

            foreach ($roots as $root) {
                $file = $root . '/' . $pluginName . '/class/' . implode('/', $parts) . '.php';
                if (file_exists($file)) {
                    include $file;
                    return;
                }
            }
        });

        // Automatic Alias Loader for Helpers and Models
        spl_autoload_register(function ($class) {
            // Only handle top-level classes (no namespace separator)
            if (strpos($class, '\\') !== false) {
                return;
            }

            // Namespaces to search implicitly
            $namespaces = [
                'GaiaAlpha\\Helper\\',
                'GaiaAlpha\\Model\\',
                'GaiaAlpha\\Controller\\'
            ];

            foreach ($namespaces as $ns) {
                $fullClass = $ns . $class;
                // class_exists will trigger the standard autoloader for the full class
                if (class_exists($fullClass)) {
                    class_alias($fullClass, $class);
                    return;
                }
            }
        });
    }



}
