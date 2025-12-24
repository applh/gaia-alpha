<?php

namespace Ecommerce\Model;

use GaiaAlpha\Model\DB;

class Order
{
    public static function create($data)
    {
        // ref generator
        $reference = 'ORD-' . strtoupper(uniqid());

        $sql = "INSERT INTO ecommerce_orders (user_id, reference, status, total, customer_email, billing_address) VALUES (?, ?, 'pending', ?, ?, ?)";
        DB::query($sql, [
            $data['user_id'] ?? null,
            $reference,
            $data['total'],
            $data['customer_email'],
            json_encode($data['billing_address'] ?? [])
        ]);

        return [
            'id' => DB::lastInsertId(),
            'reference' => $reference
        ];
    }

    public static function addItem($orderId, $item)
    {
        $sql = "INSERT INTO ecommerce_order_items (order_id, product_id, product_name, quantity, unit_price, subtotal, type, external_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        DB::query($sql, [
            $orderId,
            $item['product_id'],
            $item['product_name'],
            $item['quantity'],
            $item['unit_price'],
            $item['subtotal'],
            $item['type'],
            $item['external_id']
        ]);
    }

    public static function markPaid($id, $transactionId)
    {
        DB::query("UPDATE ecommerce_orders SET status = 'paid', transaction_id = ? WHERE id = ?", [$transactionId, $id]);
    }
}
