<?php

namespace Ecommerce\Service;

use Ecommerce\Model\Product;

class CartService
{

    public static function init()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public static function add($productId, $quantity = 1)
    {
        self::init();
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    }

    public static function getItems()
    {
        self::init();
        $items = [];
        $total = 0;

        foreach ($_SESSION['cart'] as $productId => $qty) {
            $product = Product::find($productId);
            if ($product) {
                $subtotal = $product['price'] * $qty;
                $items[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'subtotal' => $subtotal
                ];
                $total += $subtotal;
            }
        }

        return ['items' => $items, 'total' => $total];
    }

    public static function clear()
    {
        self::init();
        $_SESSION['cart'] = [];
    }
}
