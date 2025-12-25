<?php

use GaiaAlpha\Hook;
use GaiaAlpha\UiManager;
use Drawing\Controller\DrawingController;

// 1. Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('drawing', DrawingController::class);
});

// 2. Register UI Component
UiManager::registerComponent(
    'drawing',
    'plugins/Drawing/resources/js/DrawingCanvas.js',
    false // Available to all users (not just admin)
);

// 3. Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $found = false;

        if (!isset($data['user']['menu_items']) || !is_array($data['user']['menu_items'])) {
            $data['user']['menu_items'] = [];
        }

        // Add to "Apps" group if it exists, otherwise create it
        foreach ($data['user']['menu_items'] as &$item) {
            if (isset($item['id']) && $item['id'] === 'grp-apps') {
                if (!isset($item['children'])) {
                    $item['children'] = [];
                }
                // Check if already exists
                $exists = false;
                foreach ($item['children'] as $child) {
                    if ($child['view'] === 'drawing') {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $item['children'][] = [
                        'label' => 'Drawing',
                        'view' => 'drawing',
                        'icon' => 'pen-tool'
                    ];
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['user']['menu_items'][] = [
                'id' => 'grp-apps',
                'label' => 'Apps',
                'icon' => 'grid',
                'children' => [
                    [
                        'label' => 'Drawing',
                        'view' => 'drawing',
                        'icon' => 'pen-tool'
                    ]
                ]
            ];
        }
    }
    return $data;
});
