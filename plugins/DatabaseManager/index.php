<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use DatabaseManager\Controller\DatabaseController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('database', DatabaseController::class);
});
\GaiaAlpha\UiManager::registerComponent('database', 'plugins/DatabaseManager/DatabaseManager.js', true);

// Inject Menu
