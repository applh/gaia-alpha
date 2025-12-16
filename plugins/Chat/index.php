<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Chat\Controller\ChatController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    if (class_exists(ChatController::class)) {
        $controller = new ChatController();
        if (method_exists($controller, 'init')) {
            $controller->init();
        }
        $controllers['chat'] = $controller;
        Env::set('controllers', $controllers);
    }
});

// Inject Menu
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'label' => 'Projects',
            'id' => 'grp-projects',
            'children' => [
                ['label' => 'Chat', 'view' => 'chat', 'icon' => 'message-square']
            ]
        ];
    }
    return $data;
});
