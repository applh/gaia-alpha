<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Router;
use Lms\LmsController;

// Register API Routes
Hook::add('router_matched', function () {
    Router::get('/api/lms/courses', [LmsController::class, 'getCourses']);
    Router::get('/api/lms/courses/(\d+)', [LmsController::class, 'getCourse']);
    Router::post('/api/lms/courses', [LmsController::class, 'createCourse']);
}, 20);

// Integration: Listen for E-commerce orders
Hook::add('ecommerce_order_paid', function ($order, $items) {
    foreach ($items as $item) {
        // If the product type is 'course', enroll the user
        if ($item['type'] === 'course' && !empty($item['external_id'])) {
            Lms\Service\EnrollmentService::enroll($order['user_id'], $item['external_id']);
        }
    }
});
