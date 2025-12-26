<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Router;
use Lms\LmsController;

// Register Controller
Hook::add('framework_load_controllers_after', function () {
    \GaiaAlpha\Framework::registerController('lms', LmsController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent(
    'lms_dashboard',
    'plugins/Lms/resources/js/LmsDashboard.js',
    true // Admin only
);

// Integration: Listen for E-commerce orders
Hook::add('ecommerce_order_paid', function ($order, $items) {
    foreach ($items as $item) {
        // If the product type is 'course', enroll the user
        if ($item['type'] === 'course' && !empty($item['external_id'])) {
            Lms\Service\EnrollmentService::enroll($order['user_id'], $item['external_id']);
        }
    }
});
