<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use MultiSite\SiteController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {

    if (class_exists(SiteController::class)) {
        // ALWAYS fetch the latest controllers from Env to avoid overwriting updates from other plugins
        $controllers = Env::get('controllers');

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

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('sites', 'plugins/MultiSite/MultiSitePanel.js', true);


