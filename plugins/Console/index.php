<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Console\Controller\ConsoleController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {

    // We instantiate manually because it's not in the main controller loop anymore
    if (class_exists(ConsoleController::class)) {
        $controller = new ConsoleController();
        if (method_exists($controller, 'init')) {
            $controller->init();
        }

        // Register routes immediately or let framework handle it if we add to $controllers?
        // Framework::registerRoutes iterates over $controllers.
        // So we add it to the array.
        $controllers['console'] = $controller;

        // Update Env
        Env::set('controllers', $controllers);
    }
});

// Inject Menu
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) { // Admin only check essentially
        $data['user']['menu_items'][] = [
            'label' => 'System', // Target existing group
            'id' => 'grp-system',
            'children' => [
                ['label' => 'Console', 'view' => 'console', 'icon' => 'terminal']
            ]
        ];
    }
    return $data;
});
