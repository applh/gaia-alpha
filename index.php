<?php

// add class autoloading
require_once __DIR__ . '/class/autoload.php';

// start the application
\GaiaAlpha\App::web_setup(__DIR__);
\GaiaAlpha\App::run();
