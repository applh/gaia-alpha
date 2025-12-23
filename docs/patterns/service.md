# Service Pattern

Services are the workhorses of the application. They encapsulate business logic, ensuring Controllers remain thin and Models stay focused on data structure.

## Architectural Role

1.  **Business Logic Encapsulation**: Complex operations (calculating taxes, processing payments, syncing with external APIs) belong here.
2.  **Reusability**: A Service can be called by multiple Controllers, CLI commands, or MCP Tools.
3.  **State Management**: Services can be Singletons if they need to maintain state (like a database connection or cache) across the request.

## Service Types

### 1. Static Service (Utility)
Best for stateless logic where no configuration or persistent connection is needed.

```php
namespace YourPlugin\Service;

class CalculationService
{
    public static function calculateTax(float $amount): float
    {
        return $amount * 0.20;
    }
}
```

### 2. Singleton Service (Manager)
Best for resources that should be instantiated once, like database connections or API clients.

```php
namespace YourPlugin\Service;

class ConnectionManager
{
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = $this->connect();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($sql) { ... }
}
```

## Golden Sample

```php
<?php

namespace YourPlugin\Service;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Session;

class OrderProcessingService
{
    /**
     * Process a complete order flow
     */
    public static function processOrder(int $userId, array $orderData): array
    {
        // Validate input
        if (empty($orderData['items'])) {
            throw new \Exception('Order must contain items');
        }

        // Create order record
        DB::execute('
            INSERT INTO orders (user_id, total, status, created_at)
            VALUES (?, ?, ?, datetime("now"))
        ', [$userId, $orderData['total'], 'pending']);
        
        $orderId = DB::lastInsertId();

        // Insert order items
        foreach ($orderData['items'] as $item) {
            DB::execute('
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ', [$orderId, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // Return created order
        return self::getOrder($orderId);
    }

    public static function getOrder(int $orderId): ?array
    {
        $order = DB::fetch('SELECT * FROM orders WHERE id = ?', [$orderId]);
        
        if (!$order) {
            return null;
        }

        // Load order items
        $order['items'] = DB::fetchAll('
            SELECT * FROM order_items WHERE order_id = ?
        ', [$orderId]);

        return $order;
    }
    
    public static function listOrders(int $userId): array
    {
        return DB::fetchAll('
            SELECT * FROM orders 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ', [$userId]);
    }
}
```

## Database Access Best Practices

**Always use `GaiaAlpha\Model\DB` class:**

```php
use GaiaAlpha\Model\DB;

// Fetch all rows
$rows = DB::fetchAll($sql, $params);

// Fetch single row
$row = DB::fetch($sql, $params);

// Execute INSERT/UPDATE/DELETE
DB::execute($sql, $params);

// Get last inserted ID
$id = DB::lastInsertId();
```

**Writing Multi-DB Compatible SQL:**

When writing raw queries, always follow the [Multi-DB SQL Management Pattern](../core/multi_db_sql.md). 

1. Use SQLite-ish dialect (the translation layer handles it).
2. Avoid dialect-specific functions unless absolutely necessary.
3. Use `DB::getTableSchema($table)` for portable schema inspection.

**Never use:**
- ❌ `DataStore::getDb()` - Wrong class
- ❌ Direct PDO access - Use DB class instead

## Logging and Performance (Hooks)

Logging operations (API logs, Analytics, Activity) should be **pluggable** to minimize performance impact and allow easy deactivation.

### Use the Hook Pattern for Logging
Instead of calling a logging service directly from core logic, trigger a hook. This ensures the core remains fast and the logging logic is entirely optional.

**Core Logic:**
```php
// Core logic remains thin and unaware of logging implementation
Hook::run('mcp_request_handled', [
    'request' => $request,
    'duration' => $duration
]);
```

**Plugin Registration (index.php):**
```php
// Decoupled logging subscriber
Hook::add('mcp_request_handled', [McpLogger::class, 'logRequest']);
```

### Async/Passive Logging
Log entries should ideally be captured at the end of the request lifecycle (e.g., `router_dispatch_after` or `response_json_before`) to ensure they don't block the generation of the response.

## Checklist

- [ ] Resides in `YourPlugin/class/Service/`.
- [ ] Uses correct namespace `YourPlugin\Service`.
- [ ] Uses `GaiaAlpha\Model\DB` for database operations.
- [ ] Encapsulates logic that doesn't belong in a Controller.
- [ ] Validates input and throws exceptions on errors.
- [ ] Implementing logging? Use Hooks to keep it pluggable.
