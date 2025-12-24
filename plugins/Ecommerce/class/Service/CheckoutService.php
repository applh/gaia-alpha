<?php

namespace Ecommerce\Service;

use Ecommerce\Model\Order;
use Ecommerce\Model\Product;

class CheckoutService
{

    public static function processCheckout($userId, $email, $billingAddress)
    {
        // 1. Get Cart
        $cart = CartService::getItems();
        if (empty($cart['items'])) {
            throw new \Exception("Cart is empty");
        }

        // 2. Create Order
        $orderData = [
            'user_id' => $userId,
            'total' => $cart['total'],
            'customer_email' => $email,
            'billing_address' => $billingAddress
        ];

        $order = Order::create($orderData);
        $orderId = $order['id'];

        // 3. Add Items
        $itemsForHook = []; // Prepare structure for hooks
        foreach ($cart['items'] as $item) {
            $p = $item['product'];
            $lineItem = [
                'product_id' => $p['id'],
                'product_name' => $p['title'],
                'quantity' => $item['quantity'],
                'unit_price' => $p['price'],
                'subtotal' => $item['subtotal'],
                'type' => $p['type'],
                'external_id' => $p['external_id']
            ];
            Order::addItem($orderId, $lineItem);
            $itemsForHook[] = array_merge($lineItem, ['related_id' => $p['external_id']]); // standardize 'related_id' for generic usage
        }

        // 4. Fake Payment Processing (for now)
        // In real life, here we'd charge the card.
        $transactionId = 'TXN-' . uniqid();
        Order::markPaid($orderId, $transactionId);

        // 5. Clear Cart
        CartService::clear();

        // 6. Return Order info
        return [
            'order_id' => $orderId,
            'status' => 'paid',
            'items' => $itemsForHook // Return these so Controller can fire hooks or we fire them here
        ];
    }
}
