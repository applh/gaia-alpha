<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Router;
use Lms\LmsController;

// Register Controller
Hook::add('framework_load_controllers_after', function () {
    \GaiaAlpha\Framework::registerController('lms', LmsController::class);
});

// 2. Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        if (!isset($data['user']['menu_items']) || !is_array($data['user']['menu_items'])) {
            $data['user']['menu_items'] = [];
        }

        // Add as standalone or grouped. Let's make a grouping for Education?
        // Or just add to root for now as there may not be other edu plugins.
        // Actually, let's group it to be safe or clean.

        $found = false;
        foreach ($data['user']['menu_items'] as &$item) {
            if (isset($item['id']) && $item['id'] === 'grp-education') {
                if (!isset($item['children']))
                    $item['children'] = [];
                $item['children'][] = [
                    'label' => 'LMS',
                    'view' => 'lms_dashboard',
                    'icon' => 'book-open'
                ];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['user']['menu_items'][] = [
                'id' => 'grp-education',
                'label' => 'Education',
                'icon' => 'book',
                'children' => [
                    [
                        'label' => 'LMS',
                        'view' => 'lms_dashboard',
                        'icon' => 'book-open'
                    ]
                ]
            ];
        }
    }
    return $data;
});

// Integration: Listen for E-commerce orders
Hook::add('ecommerce_order_paid', function ($order, $items) {
    foreach ($items as $item) {
        // If the product type is 'course', enroll the user
        if ($item['type'] === 'course' && !empty($item['external_id'])) {
            Lms\Service\EnrollmentService::enroll($order['user_id'], $item['external_id']);
        }
    }
});
