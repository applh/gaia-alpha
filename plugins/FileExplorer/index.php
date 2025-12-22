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

// Register Routes
Hook::add('framework_register_routes', function ($router) {
    $router->add('GET', '/@/file-explorer/list', [FileExplorerController::class, 'list']);
    $router->add('GET', '/@/file-explorer/read', [FileExplorerController::class, 'read']);
    $router->add('POST', '/@/file-explorer/write', [FileExplorerController::class, 'write']);
    $router->add('POST', '/@/file-explorer/create', [FileExplorerController::class, 'create']);
    $router->add('POST', '/@/file-explorer/delete', [FileExplorerController::class, 'delete']);
    $router->add('POST', '/@/file-explorer/rename', [FileExplorerController::class, 'rename']);
    $router->add('POST', '/@/file-explorer/move', [FileExplorerController::class, 'move']);
    $router->add('POST', '/@/file-explorer/image-process', [FileExplorerController::class, 'imageProcess']);
    $router->add('GET', '/@/file-explorer/vfs', [FileExplorerController::class, 'vfsList']);
    $router->add('POST', '/@/file-explorer/vfs', [FileExplorerController::class, 'vfsCreate']);
});
