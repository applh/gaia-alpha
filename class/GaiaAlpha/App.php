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
                $task();
            }
        }
    }

    public static function web_setup(string $rootDir)
    {
        Hook::run('app_init');
        Env::set('root_dir', $rootDir);
        Env::set('controllers', []);
        Env::set('framework_tasks', [
            "step05" => "GaiaAlpha\\Framework::loadPlugins",
            "step06" => "GaiaAlpha\\Framework::appBoot",
            "step10" => "GaiaAlpha\\Framework::loadControllers",
            "step12" => "GaiaAlpha\\Framework::sortControllers",
            "step15" => "GaiaAlpha\\Framework::registerRoutes",
            "step20" => "GaiaAlpha\\Router::handle",
        ]);

        // load my-config.php
        // can change framework_tasks
        if (file_exists($rootDir . '/my-config.php')) {
            require_once $rootDir . '/my-config.php';
        }

        $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : $rootDir . '/my-data';
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
        Env::set('framework_tasks', [
            "step05" => "GaiaAlpha\\Framework::loadPlugins",
            "step06" => "GaiaAlpha\\Framework::appBoot",
            "step10" => "GaiaAlpha\\Cli::run"
        ]);

        if (file_exists($rootDir . '/my-config.php')) {
            require_once $rootDir . '/my-config.php';
        }

        $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : $rootDir . '/my-data';
        Env::set('path_data', $dataPath);

        self::registerAutoloaders();
    }

    public static function registerAutoloaders()
    {
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
    }


}
