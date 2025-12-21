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
        if (method_exists($controller, 'registerRoutes')) {
            $controller->registerRoutes();
        }

        // Register controller
        $controllers['map'] = $controller;

        // Update Env
        Env::set('controllers', $controllers);
    }
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('map', 'plugins/Map/MapPanel.js', false);


