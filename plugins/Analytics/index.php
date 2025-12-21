<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use GaiaAlpha\Request;
use Analytics\Controller\AnalyticsController;
use Analytics\Service\AnalyticsService;

// 1. Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    if (class_exists(AnalyticsController::class)) {
        $controller = new AnalyticsController();
        if (method_exists($controller, 'registerRoutes')) {
            $controller->registerRoutes();
        }
        $controllers['analytics'] = $controller;
        Env::set('controllers', $controllers);
    }
});

// 2. Register UI Component
\GaiaAlpha\UiManager::registerComponent(
    'analytics_dashboard',
    'plugins/Analytics/AnalyticsDashboard.js',
    true // Admin only
);

// 3. Inject Menu Item (Preferred for Groups/Icons)
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        $found = false;

        if (!isset($data['user']['menu_items']) || !is_array($data['user']['menu_items'])) {
            $data['user']['menu_items'] = [];
        }

        foreach ($data['user']['menu_items'] as &$item) {
            if (isset($item['id']) && $item['id'] === 'grp-reports') {
                if (!isset($item['children'])) {
                    $item['children'] = [];
                }
                $item['children'][] = [
                    'label' => 'Analytics',
                    'view' => 'analytics_dashboard',
                    'icon' => 'activity'
                ];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['user']['menu_items'][] = [
                'id' => 'grp-reports',
                'label' => 'Reports',
                'icon' => 'bar-chart-2',
                'children' => [
                    [
                        'label' => 'Analytics',
                        'view' => 'analytics_dashboard',
                        'icon' => 'activity'
                    ]
                ]
            ];
        }
    }
    return $data;
});

// 4. Track Page Visits
Hook::add('router_dispatch_after', function ($route, $params) {
    // Only track GET requests that are not API calls or admin calls
    $method = Request::server('REQUEST_METHOD');
    $path = Request::path();

    // Skip API, Admin, and non-GET requests
    if ($method !== 'GET')
        return;
    if (strpos($path, '/@/') === 0)
        return;
    if (strpos($path, '/api/') === 0)
        return;
    if (strpos($path, '/assets/') === 0)
        return;
    if (strpos($path, '/min/') === 0)
        return;

    AnalyticsService::trackVisit(
        $path,
        Request::server('HTTP_USER_AGENT'),
        Request::server('REMOTE_ADDR'),
        Request::server('HTTP_REFERER')
    );
});
