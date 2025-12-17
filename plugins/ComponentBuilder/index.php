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

        // Register routes immediately since Framework::registerRoutes() has already been called
        // or will be called before this hook's changes are visible
        if (method_exists($controller, 'registerRoutes')) {
            $controller->registerRoutes();
        }
    }
});

// Inject Menu
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        $data['user']['menu_items'][] = [
            'label' => 'System',
            'id' => 'grp-system',
            'children' => [
                ['label' => 'Component Builder', 'view' => 'component-builder', 'icon' => 'puzzle']
            ]
        ];
    }
    return $data;
});

