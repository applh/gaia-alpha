<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use DatabaseManager\Controller\DatabaseController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    // ALWAYS fetch the latest controllers from Env to avoid overwriting updates from other plugins
    $controllers = Env::get('controllers');

    if (class_exists(DatabaseController::class)) {
        $controller = new DatabaseController();
        if (method_exists($controller, 'init')) {
            $controller->init();
        }

        // Register controller
        $controllers['database'] = $controller;

        // Update Env
        Env::set('controllers', $controllers);
    }
});
\GaiaAlpha\UiManager::registerComponent('database', 'plugins/DatabaseManager/DatabaseManager.js', true);

// Inject Menu
