<?php

require_once __DIR__ . '/class/autoload.php'; // Autoloader

use GaiaAlpha\Cli;
use GaiaAlpha\App;
use GaiaAlpha\Env;

// Setup CLI environment
App::cli_setup(__DIR__);

// Run CLI
App::run();
