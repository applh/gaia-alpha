<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Router;
use Ecommerce\EcommerceController;

// Register Controller
Hook::add('framework_load_controllers_after', function () {
    \GaiaAlpha\Framework::registerController('ecommerce', EcommerceController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent(
    'ecommerce_dashboard',
    'plugins/Ecommerce/resources/js/EcommerceDashboard.js',
    true // Admin only
);

// Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        if (!isset($data['user']['menu_items'])) {
            $data['user']['menu_items'] = [];
        }

        $data['user']['menu_items'][] = [
            'id' => 'grp-business',
            'label' => 'Business',
            'icon' => 'briefcase',
            'children' => [
                [
                    'label' => 'E-commerce',
                    'view' => 'ecommerce_dashboard',
                    'icon' => 'shopping-cart'
                ]
            ]
        ];
    }
    return $data;
});
