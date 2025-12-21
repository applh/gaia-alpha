<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use ApiBuilder\Controller\ApiBuilderController;
use ApiBuilder\Controller\DynamicApiController;

// Register Controllers
Hook::add('framework_load_controllers_after', function ($controllers) {

    // Register ApiBuilderController
    if (class_exists(ApiBuilderController::class)) {
        $apiBuilder = new ApiBuilderController();
        if (method_exists($apiBuilder, 'init')) {
            $apiBuilder->init();
        }
        if (method_exists($apiBuilder, 'registerRoutes')) {
            $apiBuilder->registerRoutes();
        }
        $controllers['api-builder'] = $apiBuilder; // Key matches standard route expectations if any
    }

    // Register DynamicApiController
    if (class_exists(DynamicApiController::class)) {
        $dynamicApi = new DynamicApiController();
        if (method_exists($dynamicApi, 'init')) {
            $dynamicApi->init();
        }
        // Dynamic controller typically registers its own routes but we add it to array for completeness
        $controllers['dynamic-api'] = $dynamicApi;
    }

    // Update Env
    Env::set('controllers', $controllers);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('api-builder', 'plugins/ApiBuilder/ApiManager.js', true);


