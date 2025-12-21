<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Mail\Controller\MailController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    // ALWAYS fetch the latest controllers from Env to avoid overwriting updates from other plugins
    $controllers = Env::get('controllers');

    if (class_exists(MailController::class)) {
        $controller = new MailController();
        if (method_exists($controller, 'registerRoutes')) {
            $controller->registerRoutes();
        }
        $controllers['mail'] = $controller;
        Env::set('controllers', $controllers);
    }
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('mail/inbox', 'plugins/Mail/MailPanel.js', true);


