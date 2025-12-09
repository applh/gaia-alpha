<?php

require_once __DIR__ . '/class/autoload.php'; // Autoloader

use GaiaAlpha\Cli;
use GaiaAlpha\App;
use GaiaAlpha\Env;

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Initialize root directory
Env::set('root_dir', __DIR__);

$dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : Env::get('root_dir') . '/my-data';
$dsn = defined('GAIA_DB_DSN') ? GAIA_DB_DSN : 'sqlite:' . (defined('GAIA_DB_PATH') ? GAIA_DB_PATH : $dataPath . '/database.sqlite');
$cli = new Cli($dsn, $dataPath);
$cli->run($argv);
