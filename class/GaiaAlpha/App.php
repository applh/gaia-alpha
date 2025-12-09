<?php

namespace GaiaAlpha;

use GaiaAlpha\Controller\DbController;

class App
{
    public static function run(string $rootDir)
    {
        Env::set('root_dir', $rootDir);
        Env::set('controllers', []);

        // load my-config.php
        if (file_exists($rootDir . '/my-config.php')) {
            require_once $rootDir . '/my-config.php';
        }

        $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : $rootDir . '/my-data';
        Env::set('path_data', $dataPath);

        Router::loadControllers();
        Router::handle();
    }
}
