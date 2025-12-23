<?php

use GaiaAlpha\Hook;
use ScreenRecorder\Controller\ScreenRecorderController;

// 1. Dynamic Controller Registration
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('screen_recorder', ScreenRecorderController::class);
});

// 2. Register UI Component
\GaiaAlpha\UiManager::registerComponent('screen_recorder', 'plugins/ScreenRecorder/resources/js/ScreenRecorder.js', true);

// 3. Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $found = false;
        foreach ($data['user']['menu_items'] as &$item) {
            if (isset($item['id']) && $item['id'] === 'grp-tools') {
                $item['children'][] = [
                    'label' => 'Screen Recorder',
                    'view' => 'screen_recorder',
                    'icon' => 'video'
                ];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['user']['menu_items'][] = [
                'id' => 'grp-tools',
                'label' => 'Tools',
                'icon' => 'box',
                'children' => [
                    [
                        'label' => 'Screen Recorder',
                        'view' => 'screen_recorder',
                        'icon' => 'video'
                    ]
                ]
            ];
        }
    }
    return $data;
});
