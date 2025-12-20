<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use DatabaseConnections\Controller\ConnectionController;

// Register Routes
Hook::add('framework_register_routes', function () {
    $controller = new ConnectionController();
    $controller->registerRoutes();
});

// Inject Menu Item done via plugin.json
