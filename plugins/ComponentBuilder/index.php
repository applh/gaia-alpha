<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use ComponentBuilder\Controller\ComponentBuilderController;

// Register Controller
Hook::add('framework_load_controllers_after', function () {
    if (class_exists(ComponentBuilderController::class)) {
        $controller = new ComponentBuilderController();

        if (method_exists($controller, 'init')) {
            $controller->init();
        }

        // Get controllers from Env
        $controllers = Env::get('controllers');

        // Register controller
        $controllers['component_builder'] = $controller;

        // Update Env
        Env::set('controllers', $controllers);
    }
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('component-builder', 'plugins/ComponentBuilder/ComponentBuilder.js', true);



