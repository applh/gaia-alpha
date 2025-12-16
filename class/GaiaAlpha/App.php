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

        // Resolve Site / DB Path
        \GaiaAlpha\SiteManager::resolve();

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

        // load my-config.php
        // can change framework_tasks
        if (file_exists($rootDir . '/my-config.php')) {
            require_once $rootDir . '/my-config.php';
        }

        $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : $rootDir . '/my-data';
        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0755, true);
        }
        Env::set('path_data', $dataPath);

        self::registerAutoloaders();


    }

    public static function cli_setup(string $rootDir)
    {
        if (php_sapi_name() !== 'cli') {
            die("This script must be run from the command line.\n");
        }

        Hook::run('app_init');
        Env::set('root_dir', $rootDir);

        // Resolve Site / DB Path
        \GaiaAlpha\SiteManager::resolve();

        Env::set('framework_tasks', [
            "step00" => "GaiaAlpha\\Debug::init",
            "step05" => "GaiaAlpha\\Framework::loadPlugins",
            "step06" => "GaiaAlpha\\Framework::appBoot",
            "step10" => "GaiaAlpha\\Cli::run"
        ]);

        if (file_exists($rootDir . '/my-config.php')) {
            require_once $rootDir . '/my-config.php';
        }

        $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : $rootDir . '/my-data';
        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0755, true);
        }
        Env::set('path_data', $dataPath);

        self::registerAutoloaders();
    }

    public static function registerAutoloaders()
    {
        // 1. Core Framework Autoloader
        spl_autoload_register(function ($class) {
            // Check if class starts with GaiaAlpha\
            if (strpos($class, 'GaiaAlpha\\') === 0) {
                // Map GaiaAlpha\Foo -> class/GaiaAlpha/Foo.php
                // Note: The file passed to spl_autoload might be relative or absolute, 
                // but let's assume we are running from root or have defined root.
                // Actually, App.php is in class/GaiaAlpha/App.php. 
                // dirname(__DIR__) is class/GaiaAlpha.
                // dirname(dirname(__DIR__)) is root/class.

                // Let's rely on standard logic independent of cwd if possible, 
                // but using __DIR__ inside App.php is safest.

                // Class: GaiaAlpha\Sub\Foo
                // Path: .../class/GaiaAlpha/Sub/Foo.php

                $baseDir = dirname(__DIR__) . '/'; // .../class/GaiaAlpha/

                // Remove prefix 'GaiaAlpha\'
                $relativeClass = substr($class, 11);

                $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

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

            // New path: data_path/plugins/{PluginName}/class/{RestOfClass}.php
            $dataPath = Env::get('path_data');
            // If path_data is not set, we cannot resolve plugins
            if (!$dataPath) {
                return;
            }

            $file = $dataPath . '/plugins/' . $pluginName . '/class/' . implode('/', $parts) . '.php';

            if (file_exists($file)) {
                include $file;
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
