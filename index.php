<?php

// Bootstrap manually since we removed autoload.php
require_once __DIR__ . '/class/GaiaAlpha/App.php';
// start the application
\GaiaAlpha\App::web_setup(__DIR__);
\GaiaAlpha\App::run();
