# E-commerce Plugin Proposal

## 1. Overview
The **E-commerce Plugin** will provide a complete solution for selling products and services within Gaia Alpha. It allows for product management, cart functionality, checkout processing, and order management. Ideally suited for selling digital goods, courses (via LMS), and physical items.

## 2. Goals
- **Product Management**: Flexible product types (Physical, Digital, Service, External - e.g. Course).
- **Cart & Checkout**: Session-based cart and secure checkout flow.
- **Order Management**: Order history, status updates, emails.
- **Payment Gateways**: Pluggable driver system for payments (Stripe, PayPal, etc.).
- **Extensibility**: Allow other plugins (like LMS) to define custom product types and fulfillment logic.

## 3. Database Schema
The plugin will define its tables in `plugins/Ecommerce/schema.sql`.

```sql
-- Products
CREATE TABLE IF NOT EXISTS ecommerce_products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    sku VARCHAR(100),
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    sale_price DECIMAL(10, 2) NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    type VARCHAR(50) DEFAULT 'simple', -- simple, course, license_key
    external_id VARCHAR(255) NULL,     -- ID in the external system (e.g., Course ID)
    description TEXT,
    stock_status VARCHAR(20) DEFAULT 'in_stock',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Orders
CREATE TABLE IF NOT EXISTS ecommerce_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,              -- Null for guest checkout?
    reference VARCHAR(20) NOT NULL UNIQUE,
    status VARCHAR(20) DEFAULT 'pending', -- pending, paid, failed, refunded, completed
    total DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),       -- Stripe PaymentIntent ID, etc.
    customer_email VARCHAR(255),
    billing_address TEXT,              -- JSON
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Order Items
CREATE TABLE IF NOT EXISTS ecommerce_order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    variant_id INTEGER NULL,
    product_name VARCHAR(255),
    quantity INTEGER DEFAULT 1,
    unit_price DECIMAL(10, 2),
    subtotal DECIMAL(10, 2),
    type VARCHAR(50),                  -- Copy of product type for fulfillment
    external_id VARCHAR(255),          -- Copy of external ID
    FOREIGN KEY(order_id) REFERENCES ecommerce_orders(id) ON DELETE CASCADE
);
```

## 4. Architecture

### 4.1 Namespace & Structure
Namespace: `Ecommerce`
Path: `plugins/Ecommerce/`

- `plugin.json`: Metadata.
- `index.php`: Entry point.
- `class/Product.php`, `class/Order.php`: Models.
- `class/CartService.php`: Session-based cart.
- `class/PaymentGatewayInterface.php`: Interface for payment providers.
- `class/Gateways/StripeGateway.php`: Example implementation.
- `class/CheckoutService.php`: Handles order creation and payment processing.

### 4.2 Integration Hooks (The "Connectable" Pattern)

**1. Defining Product Types**
Other plugins can register product types.
`Hook::add('ecommerce_register_product_types', ...)`

**2. Fulfillment (The most important part)**
When an order is paid, E-commerce fires an event.
`Hook::run('ecommerce_order_paid', $order, $items);`

**Example (LMS Integration)**:
The LMS plugin will listen to this hook. It checks if any item in the order has `type === 'course'`. If so, it enrolls the user in the course identified by `external_id`.

**3. Pricing Overrides**
`Hook::run('ecommerce_get_price', $product, $context)`
Allows dynamic pricing (e.g., discounts for members).

### 4.3 Payment Gateways
The system will support multiple gateways via a Strategy pattern.
config `ecommerce.gateways` = `['stripe', 'paypal']`.

## 5. UI Components
1.  **Product Manager**: Admin UI to add/edit products.
2.  **Order Manager**: Admin UI to view/refund orders.
3.  **Cart & Checkout**: Frontend components.
4.  **"Buy Now" Button**: Reusable component that can be embedded in LMS course pages.

## 6. Security
- **PCI Compliance**: Never store credit card numbers. Use tokenization (e.g. Stripe Elements).
- **CSRF Protection**: Critical for checkout forms.
- **Webhooks**: Securely handle async payment notifications (Stripe webhooks) to update order status.
