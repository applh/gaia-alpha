<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use ApiBuilder\Controller\ApiBuilderController;
use ApiBuilder\Controller\DynamicApiController;

// Register Controllers
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('api-builder', ApiBuilderController::class);
    \GaiaAlpha\Framework::registerController('dynamic-api', DynamicApiController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('api-builder', 'plugins/ApiBuilder/ApiManager.js', true);


