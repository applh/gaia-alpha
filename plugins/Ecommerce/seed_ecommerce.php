<?php

require_once __DIR__ . '/../../class/GaiaAlpha/App.php';
\GaiaAlpha\App::registerAutoloaders();
\GaiaAlpha\App::web_setup(__DIR__ . '/../..');

use GaiaAlpha\Model\DB;
use Ecommerce\Model\Product;
use Ecommerce\Model\Order;

echo "Seeding Ecommerce data...\n";

// Clear existing data
DB::query("DELETE FROM ecommerce_order_items");
DB::query("DELETE FROM ecommerce_orders");
DB::query("DELETE FROM ecommerce_products");

// Sample Products
$products = [
    [
        'title' => 'Vintage Camera',
        'slug' => 'vintage-camera',
        'sku' => 'CAM-001',
        'price' => 299.00,
        'type' => 'simple',
        'description' => 'A beautiful vintage camera in excellent condition.'
    ],
    [
        'title' => 'Wireless Headphones',
        'slug' => 'wireless-headphones',
        'sku' => 'AUD-002',
        'price' => 149.99,
        'type' => 'simple',
        'description' => 'Noise-cancelling wireless headphones with 30-hour battery life.'
    ],
    [
        'title' => 'Smart Watch',
        'slug' => 'smart-watch',
        'sku' => 'WAT-003',
        'price' => 199.50,
        'type' => 'simple',
        'description' => 'Fitness tracker and smartwatch with heart rate monitor.'
    ],
    [
        'title' => 'Mechanical Keyboard',
        'slug' => 'mechanical-keyboard',
        'sku' => 'KEY-004',
        'price' => 89.00,
        'type' => 'simple',
        'description' => 'RGB mechanical keyboard with blue switches.'
    ],
    [
        'title' => 'UltraWide Monitor',
        'slug' => 'ultrawide-monitor',
        'sku' => 'MON-005',
        'price' => 450.00,
        'type' => 'simple',
        'description' => '34-inch UltraWide curved gaming monitor.'
    ]
];

$productIds = [];
foreach ($products as $p) {
    $productIds[] = Product::create($p);
    echo "Created product: {$p['title']}\n";
}

// Sample Orders
$orders = [
    [
        'user_id' => 1,
        'customer_email' => 'john.doe@example.com',
        'total' => 448.99,
        'billing_address' => ['city' => 'New York', 'country' => 'USA'],
        'items' => [
            ['product_id' => $productIds[0], 'qty' => 1],
            ['product_id' => $productIds[1], 'qty' => 1]
        ]
    ],
    [
        'user_id' => 2,
        'customer_email' => 'jane.smith@example.com',
        'total' => 199.50,
        'billing_address' => ['city' => 'London', 'country' => 'UK'],
        'items' => [
            ['product_id' => $productIds[2], 'qty' => 1]
        ]
    ],
    [
        'user_id' => 1,
        'customer_email' => 'john.doe@example.com',
        'total' => 89.00,
        'billing_address' => ['city' => 'New York', 'country' => 'USA'],
        'items' => [
            ['product_id' => $productIds[3], 'qty' => 1]
        ]
    ]
];

foreach ($orders as $o) {
    $orderData = Order::create($o);
    echo "Created order: {$orderData['reference']}\n";

    foreach ($o['items'] as $item) {
        $p = Product::find($item['product_id']);
        Order::addItem($orderData['id'], [
            'product_id' => $p['id'],
            'product_name' => $p['title'],
            'quantity' => $item['qty'],
            'unit_price' => $p['price'],
            'subtotal' => $p['price'] * $item['qty'],
            'type' => $p['type'],
            'external_id' => $p['external_id']
        ]);
    }

    // Mark some as paid
    if (rand(0, 1)) {
        Order::markPaid($orderData['id'], 'txn_' . uniqid());
    }
}

echo "Seeding complete!\n";
