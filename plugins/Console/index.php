<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Console\Controller\ConsoleController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('console', ConsoleController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('console', 'plugins/Console/ConsolePanel.js', true);


