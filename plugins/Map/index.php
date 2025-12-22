<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use Map\Controller\MapController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('map', MapController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('map', 'plugins/Map/MapPanel.js', false);


