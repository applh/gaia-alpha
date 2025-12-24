<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Router;
use Ecommerce\EcommerceController;

// Register API Routes
Hook::add('router_matched', function () {
    Router::get('/api/ecommerce/products', [EcommerceController::class, 'getProducts']);
    Router::get('/api/ecommerce/cart', [EcommerceController::class, 'getCart']);
    Router::post('/api/ecommerce/cart', [EcommerceController::class, 'addToCart']);
    Router::post('/api/ecommerce/checkout', [EcommerceController::class, 'checkout']);
}, 20);
