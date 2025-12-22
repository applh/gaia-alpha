<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use DatabaseConnections\Controller\ConnectionController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('db-connections', ConnectionController::class);
});

// Inject Menu Item done via plugin.json
