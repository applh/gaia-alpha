<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use GaiaAlpha\Framework;
use GaiaAlpha\UiManager;
use FileExplorer\Controller\FileExplorerController;

// Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    Framework::registerController('file-explorer', FileExplorerController::class);
});

// Register UI Component
UiManager::registerComponent('file_explorer', 'plugins/FileExplorer/resources/js/FileExplorer.js', true);

// Inject Menu
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'id' => 'grp-system',
            'label' => 'System',
            'icon' => 'settings',
            'children' => [
                [
                    'label' => 'File Explorer',
                    'view' => 'file_explorer',
                    'icon' => 'folder'
                ]
            ]
        ];
    }
    return $data;
});


