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
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        // Find System/Tools group or create
        if (!isset($data['user']['menu_items']) || !is_array($data['user']['menu_items'])) {
            $data['user']['menu_items'] = [];
        }

        $found = false;
        foreach ($data['user']['menu_items'] as &$item) {
            if (isset($item['id']) && $item['id'] === 'grp-system') {
                if (!isset($item['children']))
                    $item['children'] = [];
                $item['children'][] = [
                    'label' => 'Databases',
                    'view' => 'database',
                    'icon' => 'database'
                ];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['user']['menu_items'][] = [
                'id' => 'grp-system',
                'label' => 'System',
                'icon' => 'settings',
                'children' => [
                    [
                        'label' => 'Databases',
                        'view' => 'database',
                        'icon' => 'database'
                    ]
                ]
            ];
        }
    }
    return $data;
});
