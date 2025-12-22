<?php

use GaiaAlpha\Hook;
use NodeEditor\Controller\NodeEditorController;

// 1. Dynamic Controller Registration
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('node_editor', NodeEditorController::class);
});

// 2. Register UI Component
\GaiaAlpha\UiManager::registerComponent('node_editor', 'plugins/NodeEditor/resources/js/NodeEditor.js', true);

// 3. Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        // Find Tools group or create if doesn't exist (simplified logic here, better to check)
        $found = false;
        foreach ($data['user']['menu_items'] as &$item) {
            if (isset($item['id']) && $item['id'] === 'grp-tools') {
                $item['children'][] = [
                    'label' => 'Node Editor',
                    'view' => 'node_editor',
                    'icon' => 'git-commit' // Good icon for nodes
                ];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['user']['menu_items'][] = [
                'id' => 'grp-tools',
                'label' => 'Tools',
                'icon' => 'pen-tool',
                'children' => [
                    [
                        'label' => 'Node Editor',
                        'view' => 'node_editor',
                        'icon' => 'git-commit'
                    ]
                ]
            ];
        }
    }
    return $data;
});
