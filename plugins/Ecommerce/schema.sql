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
