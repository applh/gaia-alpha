<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use ApiAnalytics\Service\ApiLogger;
use ApiAnalytics\Service\ApiStatsService;
use ApiAnalytics\Controller\ApiStatsController;

// 1. Register Logging Hooks
Hook::add('router_dispatch_before', function () {
    ApiLogger::startTimer();
});

Hook::add('router_matched', function ($route, $matches) {
    if (isset($route['path'])) {
        ApiLogger::setPattern($route['path']);
    }
});

Hook::add('router_dispatch_after', function () {
    // We can't strictly get the status code here if it hasn't been set yet
    // but most controllers will have sent it. 
    // Response::json often doesn't return, it just echoes.
});

// Since we want to capture the status code, we use response_json_before
Hook::add('response_json_before', function (&$context) {
    ApiLogger::log($context['status'] ?? 200);
});

// For non-JSON responses (404s, errors)
Hook::add('router_404', function ($uri) {
    if (strpos($uri, '/api/') === 0 || strpos($uri, '/@/') === 0) {
        ApiLogger::startTimer(); // Ensure timer exists if 404 happened early
        ApiLogger::log(404);
    }
});

// 2. Register Stats Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    $controllers = Env::get('controllers');
    if (class_exists(ApiStatsController::class)) {
        $controller = new ApiStatsController();
        if (method_exists($controller, 'registerRoutes')) {
            $controller->registerRoutes();
        }
        $controllers['api-analytics'] = $controller;
        Env::set('controllers', $controllers);
    }
});

// 3. Register UI Component
\GaiaAlpha\UiManager::registerComponent(
    'api_dashboard',
    'plugins/ApiAnalytics/resources/js/ApiDashboard.js',
    true // Admin only
);

// 4. Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        if (!isset($data['user']['menu_items']) || !is_array($data['user']['menu_items'])) {
            $data['user']['menu_items'] = [];
        }

        foreach ($data['user']['menu_items'] as &$item) {
            if (isset($item['id']) && $item['id'] === 'grp-reports') {
                if (!isset($item['children']))
                    $item['children'] = [];
                $item['children'][] = [
                    'label' => 'API Usage',
                    'view' => 'api_dashboard',
                    'icon' => 'activity'
                ];
                break;
            }
        }
    }
    return $data;
});
