<?php

use GaiaAlpha\Hook;
use Slides\Controller\SlidesController;

// 1. Dynamic Controller Registration
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('slides', SlidesController::class);
});

// 2. Register UI Component
\GaiaAlpha\UiManager::registerComponent('slides', 'plugins/Slides/resources/js/SlidesEditor.js', true);

// 3. Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $found = false;
        foreach ($data['user']['menu_items'] as &$item) {
            if (isset($item['id']) && $item['id'] === 'grp-create') {
                $item['children'][] = [
                    'label' => 'Slides',
                    'view' => 'slides',
                    'icon' => 'monitor'
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
                        'label' => 'Slides',
                        'view' => 'slides',
                        'icon' => 'monitor'
                    ]
                ]
            ];
        }
    }
    return $data;
});
