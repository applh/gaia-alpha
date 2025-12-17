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

Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        // Inject into existing Content group if possible, or add as new
        $data['user']['menu_items'][] = [
            'id' => 'grp-content', // Match existing group ID
            'children' => [
                [
                    'label' => 'Maps', // Changed back to Maps to match user expectation of "Content / Maps"
                    'view' => 'map',
                    'icon' => 'map',
                    'path' => '/admin/map'
                ]
            ]
        ];
    }
    return $data;
});
