<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use MediaLibrary\Controller\MediaLibraryController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    $controllers = Env::get('controllers');

    if (class_exists(MediaLibraryController::class)) {
        $controller = new MediaLibraryController();
        if (method_exists($controller, 'init')) {
            $controller->init();
        }
        if (method_exists($controller, 'registerRoutes')) {
            $controller->registerRoutes();
        }
        $controllers['media_library'] = $controller;
        Env::set('controllers', $controllers);
    }
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
