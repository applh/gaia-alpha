<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use MediaLibrary\Controller\MediaLibraryController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('media_library', MediaLibraryController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('media_library', 'plugins/MediaLibrary/MediaLibrary.js', true);

// Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        // Find or create Content group
        $contentGroupIndex = null;
        foreach ($data['user']['menu_items'] as $index => $item) {
            if (($item['id'] ?? '') === 'grp-content') {
                $contentGroupIndex = $index;
                break;
            }
        }

        if ($contentGroupIndex !== null) {
            // Add to existing Content group
            $data['user']['menu_items'][$contentGroupIndex]['children'][] = [
                'label' => 'Media Library',
                'view' => 'media_library',
                'icon' => 'image'
            ];
        } else {
            // Create new Content group
            $data['user']['menu_items'][] = [
                'id' => 'grp-content',
                'label' => 'Content',
                'icon' => 'folder',
                'children' => [
                    [
                        'label' => 'Media Library',
                        'view' => 'media_library',
                        'icon' => 'image'
                    ]
                ]
            ];
        }
    }
    return $data;
});
