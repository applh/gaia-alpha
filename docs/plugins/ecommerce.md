# Ecommerce Plugin

The **Ecommerce** plugin provides a comprehensive solution for selling products and services within Gaia Alpha. It supports physical goods, digital downloads, and external integrations (like LMS courses).

## Features

- **Flexible Product Types**: Support for 'simple', 'digital', and 'course' product types.
- **Shopping Cart**: Session-based cart management.
- **Checkout Flow**: Process orders and handle payments (simulated).
- **Order Management**: Track order status from 'pending' to 'paid'.
- **Extensible Integration**: Hook-based architecture allows other plugins to react to purchases.

## Database Schema

- `ecommerce_products`: Product catalog.
- `ecommerce_orders`: Order records.
- `ecommerce_order_items`: Line items for each order.

## Usage

### Managing Products
Products can be managed via the database or API. Key fields:
- `type`: Defines the product behavior (e.g., `course`).
- `external_id`: Connects the product to an external resource (e.g., LMS Course ID).
- `price`: Unit price.

### Checkout Process
1. **Add to Cart**: Users add items to their session cart.
2. **Checkout**: User submits billing info.
3. **Payment**: System processes payment (mocked for now).
4. **Fulfillment**: `ecommerce_order_paid` hook is fired.

## Integration Hooks

### `ecommerce_order_paid`
Fired when an order changes status to 'paid'.

**Arguments:**
- `order_id` (int): ID of the paid order.
- `user_id` (int): ID of the customer.
- `items` (array): Array of order items.

**Example Usage (LMS Integration):**
```php
Hook::add('ecommerce_order_paid', function($order, $items) {
    foreach ($items as $item) {
        if ($item['type'] === 'course') {
             // Enroll user in the course
             EnrollmentService::enroll($order['user_id'], $item['external_id']);
        }
    }
});
```

## API Reference

### Get Products
**GET** `/api/ecommerce/products`

### Add to Cart
**POST** `/api/ecommerce/cart`
Body: `{ "product_id": 123, "quantity": 1 }`

### Get Cart
**GET** `/api/ecommerce/cart`

### Checkout
**POST** `/api/ecommerce/checkout`
Body: `{ "email": "user@example.com", "address": "123 Main St" }`

## Frontend Components

Located in `plugins/Ecommerce/resources/js/`:

- **ProductList.js**: Displays a grid of products with "Add to Cart" buttons.
- **Cart.js**: Displays cart contents, total, and checkout form.
