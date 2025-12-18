<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Map\Controller\MapController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {

    if (class_exists(MapController::class)) {
        $controller = new MapController();
        if (method_exists($controller, 'init')) {
            $controller->init();
        }

        // Register controller
        $controllers['map'] = $controller;

        // Update Env
        Env::set('controllers', $controllers);
    }
});


