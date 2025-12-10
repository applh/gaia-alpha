<?php

namespace GaiaAlpha;

use GaiaAlpha\Controller\DbController;

class App
{
    public static function run()
    {
        foreach (Env::get('framework_tasks') as $step => $task) {
            if (is_callable($task)) {
                $task();
            }
        }
    }

    public static function web_setup(string $rootDir)
    {
        Env::set('root_dir', $rootDir);
        Env::set('controllers', []);
        Env::set('framework_tasks', [
            "step10" => "GaiaAlpha\Framework::loadControllers",
            "step12" => "GaiaAlpha\Framework::sortControllers",
            "step15" => "GaiaAlpha\Framework::registerRoutes",
            "step20" => "GaiaAlpha\Router::handle",
        ]);

        // load my-config.php
        // can change framework_tasks
        if (file_exists($rootDir . '/my-config.php')) {
            require_once $rootDir . '/my-config.php';
        }

        $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : $rootDir . '/my-data';
        Env::set('path_data', $dataPath);
    }

    public static function cli_setup(string $rootDir)
    {
        if (php_sapi_name() !== 'cli') {
            die("This script must be run from the command line.\n");
        }

        Env::set('root_dir', $rootDir);
        Env::set('framework_tasks', [
            "step10" => "GaiaAlpha\Cli::run"
        ]);

        if (file_exists($rootDir . '/my-config.php')) {
            require_once $rootDir . '/my-config.php';
        }

        $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : $rootDir . '/my-data';
        Env::set('path_data', $dataPath);
    }


}
