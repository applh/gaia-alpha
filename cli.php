<?php

require_once __DIR__ . '/class/autoload.php'; // Autoloader

use GaiaAlpha\Cli;

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

$cli = new Cli(__DIR__ . '/my-data/database.sqlite');
$cli->run($argv);
