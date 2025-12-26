<?php

namespace Ecommerce;

use GaiaAlpha\Request;
use GaiaAlpha\Response;
use Ecommerce\Model\Product;
use Ecommerce\Service\CartService;
use Ecommerce\Service\CheckoutService;

class EcommerceController
{

    public function getProducts()
    {
        $products = Product::all();
        Response::json($products);
    }

    public function addToCart()
    {
        $data = Request::input();
        $productId = $data['product_id'];
        $qty = $data['quantity'] ?? 1;

        CartService::add($productId, $qty);
        Response::json(['message' => 'Added to cart', 'cart' => CartService::getItems()]);
    }

    public function getCart()
    {
        Response::json(CartService::getItems());
    }

    public function checkout()
    {
        $data = Request::input();
        // Assuming mocked user for now or get from auth
        // $userId = Auth::id() ?? null;
        $userId = 1; // Testing

        try {
            $result = CheckoutService::processCheckout($userId, $data['email'], $data['address'] ?? []);

            // Fire hook for external integrations (e.g. LMS)
            \GaiaAlpha\Hook::run('ecommerce_order_paid', ['user_id' => $userId, 'order_id' => $result['order_id']], $result['items']);

            Response::json(['success' => true, 'order' => $result]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function getStats()
    {
        $totalSales = \GaiaAlpha\Model\DB::fetch("SELECT SUM(total) as total FROM ecommerce_orders WHERE status = 'paid'")['total'] ?? 0;
        $orderCount = \GaiaAlpha\Model\DB::fetch("SELECT COUNT(*) as count FROM ecommerce_orders")['count'] ?? 0;
        $productCount = \GaiaAlpha\Model\DB::fetch("SELECT COUNT(*) as count FROM ecommerce_products")['count'] ?? 0;

        Response::json([
            'total_sales' => round($totalSales, 2),
            'order_count' => $orderCount,
            'product_count' => $productCount
        ]);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::get('/api/ecommerce/products', [$this, 'getProducts']);
        \GaiaAlpha\Router::get('/api/ecommerce/stats', [$this, 'getStats']);
        \GaiaAlpha\Router::get('/api/ecommerce/cart', [$this, 'getCart']);
        \GaiaAlpha\Router::post('/api/ecommerce/cart', [$this, 'addToCart']);
        \GaiaAlpha\Router::post('/api/ecommerce/checkout', [$this, 'checkout']);
    }
}
