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
}
