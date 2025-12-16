<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use MultiSite\SiteController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {

    if (class_exists(SiteController::class)) {
        $controller = new SiteController();
        if (method_exists($controller, 'init')) {
            $controller->init();
        }

        // Register controller
        $controllers['site'] = $controller;

        // Update Env
        Env::set('controllers', $controllers);
    }
});

// Inject Menu
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        $data['user']['menu_items'][] = [
            'label' => 'System',
            'id' => 'grp-system',
            'children' => [
                ['label' => 'Sites', 'view' => 'sites', 'icon' => 'server']
            ]
        ];
    }
    return $data;
});
