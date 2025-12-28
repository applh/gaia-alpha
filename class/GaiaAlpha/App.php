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
        // 0. Manual Bootstrap (Critical Core)
        require_once __DIR__ . '/Env.php';
        require_once __DIR__ . '/Hook.php';

        Env::set('autoloaders', [
            [self::class, 'autoloadFramework'],
            [self::class, 'autoloadPlugins'],
            [self::class, 'autoloadAliases']
        ]);
        self::registerAutoloaders();

        // 1. Determine Data Path first
        $dataPath = getenv('GAIA_DATA_PATH') ?: $rootDir . '/my-data';

        Hook::run('app_init');
        Env::set('root_dir', $rootDir);

        File::requireOnce($rootDir . '/loader.php');

        File::makeDirectory($dataPath);
        Env::set('path_data', $dataPath);

        // Check for Install Context
        if (Request::context() === 'install') {
            self::install_setup($rootDir);
            return;
        }

        Env::set('controllers', []);
        Env::set('framework_tasks', [
            "step01" => "GaiaAlpha\\Response::startBuffer",
            "step04" => "GaiaAlpha\\App::init",
            "step05" => "GaiaAlpha\\Framework::loadPlugins",
            "step06" => "GaiaAlpha\\Framework::appBoot",
            "step10" => "GaiaAlpha\\Framework::loadControllers",
            "step12" => "GaiaAlpha\\Framework::sortControllers",
            "step15" => "GaiaAlpha\\Framework::registerRoutes",
            "step18" => "GaiaAlpha\\Controller\\InstallController::checkInstalled",
            "step20" => "GaiaAlpha\\Router::handle",
            "step99" => "GaiaAlpha\\Response::flush",
        ]);


    }

    public static function cli_setup(string $rootDir)
    {
        if (php_sapi_name() !== 'cli') {
            die("This script must be run from the command line.\n");
        }

        // 0. Manual Bootstrap (Critical Core)
        require_once __DIR__ . '/Env.php';
        require_once __DIR__ . '/Hook.php';

        Env::set('autoloaders', [
            [self::class, 'autoloadFramework'],
            [self::class, 'autoloadPlugins'],
            [self::class, 'autoloadAliases']
        ]);
        self::registerAutoloaders();

        // 1. Determine Data Path first
        $dataPath = getenv('GAIA_DATA_PATH') ?: $rootDir . '/my-data';

        Hook::run('app_init');
        Env::set('root_dir', $rootDir);
        File::requireOnce($rootDir . '/loader.php');

        File::makeDirectory($dataPath);
        Env::set('path_data', $dataPath);

        Env::set('framework_tasks', [
            "step04" => "GaiaAlpha\\App::init",
            "step05" => "GaiaAlpha\\Framework::loadPlugins",
            "step06" => "GaiaAlpha\\Framework::appBoot",
            "step10" => "GaiaAlpha\\Cli::run"
        ]);


    }

    public static function install_setup(string $rootDir)
    {
        // 0. Manual Bootstrap (Critical Core)
        require_once __DIR__ . '/Env.php';
        require_once __DIR__ . '/Hook.php';

        // Define minimal data path for installation lock check (already done in Request usually)
        $dataPath = getenv('GAIA_DATA_PATH') ?: $rootDir . '/my-data';

        Hook::run('app_init');
        Env::set('root_dir', $rootDir);

        // Define minimal data path for installation lock check (already done in Request usually)
        // Already defined above
        Env::set('path_data', $dataPath);

        // Minimal Framework Tasks for Installation
        Env::set('framework_tasks', [
            "step01" => "GaiaAlpha\\Response::startBuffer",
            // Skip loading plugins or safely load core only if needed. For now skip.
            "step06" => "GaiaAlpha\\Framework::appBoot",
            // Manually register InstallController
            "step10" => "GaiaAlpha\\Framework::registerInstallController",
            "step15" => "GaiaAlpha\\Framework::registerRoutes",
            "step18" => "GaiaAlpha\\Controller\\InstallController::checkInstalled",
            "step20" => "GaiaAlpha\\Router::handle",
            "step99" => "GaiaAlpha\\Response::flush",
        ]);


    }

    public static function init()
    {
        $rootDir = Env::get('root_dir');
        $dataPath = Env::get('path_data');

        // 2. Load Config
        if (!File::requireOnce($dataPath . '/config.php')) {
            File::requireOnce($rootDir . '/my-config.php');
        }

        // Resolve Site / DB Path
        \GaiaAlpha\SiteManager::resolve();

        // Load Global Configuration for Admin Slug
        if (class_exists('\\GaiaAlpha\\Model\\DataStore')) {
            $globalConfig = \GaiaAlpha\Model\DataStore::getAll(0, 'global_config');
            if (isset($globalConfig['admin_slug'])) {
                Env::set('admin_prefixes', ['/@/' . $globalConfig['admin_slug']]);
            }
        }
    }

    public static function registerAutoloaders()
    {
        Hook::run('app_autoload_register');

        foreach (Env::get('autoloaders') as $autoloader) {
            spl_autoload_register($autoloader);
        }
    }

    public static function autoloadFramework($class)
    {
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
    }

    public static function autoloadPlugins($class)
    {
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
    }

    public static function autoloadAliases($class)
    {
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
    }



}
