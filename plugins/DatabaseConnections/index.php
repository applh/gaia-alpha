<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use DatabaseConnections\Controller\ConnectionController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    $controllers = Env::get('controllers');
    if (class_exists(DatabaseConnections\Controller\ConnectionController::class)) {
        $controller = new DatabaseConnections\Controller\ConnectionController();
        if (method_exists($controller, 'registerRoutes')) {
            $controller->registerRoutes();
        }
        $controllers['db-connections'] = $controller;
        Env::set('controllers', $controllers);
    }
});

// Inject Menu Item done via plugin.json
