<?php

use GaiaAlpha\Hook;
use Drawing\Controller\DrawingController;

// 1. Dynamic Controller Registration
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('drawing', DrawingController::class);
});

// 2. Register UI Component
\GaiaAlpha\UiManager::registerComponent('drawing', 'plugins/Drawing/resources/js/DrawingCanvas.js', true);

// 3. Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $found = false;
        foreach ($data['user']['menu_items'] as &$item) {
            if (isset($item['id']) && $item['id'] === 'grp-create') {
                $item['children'][] = [
                    'label' => 'Drawing',
                    'view' => 'drawing',
                    'icon' => 'pen-tool'
                ];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['user']['menu_items'][] = [
                'id' => 'grp-create',
                'label' => 'Create',
                'icon' => 'plus-circle',
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
