<?php

// Bootstrap manually since we removed autoload.php
require_once __DIR__ . '/class/GaiaAlpha/App.php';
\GaiaAlpha\App::registerAutoloaders();

use GaiaAlpha\Cli;
use GaiaAlpha\App;
use GaiaAlpha\Env;

// Setup CLI environment
App::cli_setup(__DIR__);

// Run CLI
App::run();
