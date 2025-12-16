<?php

use GaiaAlpha\Hook;
use DatabaseManager\Controller\DatabaseController;

// Register Routes
$dbController = new DatabaseController();
$dbController->registerRoutes();

// Inject Menu
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        $data['user']['menu_items'][] = [
            'label' => 'System',
            'id' => 'grp-system',
            'children' => [
                ['label' => 'Databases', 'view' => 'database', 'icon' => 'database']
            ]
        ];
    }
    return $data;
});
