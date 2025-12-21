<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Chat\Controller\ChatController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    $controllers = Env::get('controllers');
    if (class_exists(ChatController::class)) {
        $controller = new ChatController();
        if (method_exists($controller, 'init')) {
            $controller->init();
        }
        if (method_exists($controller, 'registerRoutes')) {
            $controller->registerRoutes();
        }
        $controllers['chat'] = $controller;
        Env::set('controllers', $controllers);
    }
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('chat', 'plugins/Chat/ChatPanel.js', false);

// Inject Menu

