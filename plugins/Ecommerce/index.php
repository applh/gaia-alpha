<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Router;
use Ecommerce\EcommerceController;

// Register Controller
Hook::add('framework_load_controllers_after', function () {
    \GaiaAlpha\Framework::registerController('ecommerce', EcommerceController::class);
});
// 2. Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        $found = false;
        // Ensure menu_items structure exists
        if (!isset($data['user']['menu_items']) || !is_array($data['user']['menu_items'])) {
            $data['user']['menu_items'] = [];
        }

        // Try to add to existing 'Business' group if it exists, or create new
        foreach ($data['user']['menu_items'] as &$item) {
            if (isset($item['id']) && $item['id'] === 'grp-business') {
                if (!isset($item['children']))
                    $item['children'] = [];
                $item['children'][] = [
                    'label' => 'E-commerce',
                    'view' => 'ecommerce_dashboard', // Assuming view exists or will exist
                    'icon' => 'shopping-cart'
                ];
                $found = true;
                break;
            }
        }

        if (!$found) {
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
    }
    return $data;
});
