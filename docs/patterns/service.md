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

use YourPlugin\Model\Order;

class OrderProcessingService
{
    /**
     * Process a complete order flow
     */
    public function processOrder(Order $order): bool
    {
        if (!$this->validateStock($order)) {
            return false;
        }

        $this->chargePayment($order);
        $this->sendEmail($order);
        
        return true;
    }

    private function validateStock(Order $order): bool
    {
        // Logic to check stock
        return true;
    }
    
    // ...
}
```

## Checklist

- [ ] Resides in `YourPlugin/class/Service/`.
- [ ] Uses correct namespace `YourPlugin\Service`.
- [ ] Encapsulates logic that doesn't belong in a Controller.
