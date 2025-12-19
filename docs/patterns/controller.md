# Controller Pattern

Controllers are the core of the framework's logic. They reside in `YourPlugin/class/Controller/` and are automatically loaded via **PSR-4 Autoloading**.

## Architectural Role

1.  **Request Handling**: Controllers bridge HTTP requests to Model/Service logic.
2.  **Modular Logic**: To keep controllers clean, offload complex logic to specialized Service or Model classes within the same PSR-4 namespace.
3.  **Dynamic Integration**: Controllers can be discovered by the framework or other plugins (like `McpServer`) to expose features dynamically.

## Golden Sample

```php
<?php

namespace YourPlugin\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;

class YourController extends BaseController
{
    /**
     * Required: Register your routes here.
     */
    public function registerRoutes()
    {
        Router::add('GET', '/@/your-plugin/items', [$this, 'index']);
        Router::add('POST', '/@/your-plugin/items', [$this, 'create']);
    }

    public function index()
    {
        $this->requireAuth();
        $this->jsonResponse(['items' => []]);
    }
    
    public function create()
    {
        $this->requireAuth();
        $input = $this->getJsonInput();
        // ... Logic ...
        $this->jsonResponse(['success' => true]);
    }
}
```

## Key Features

1.  **Autoloading**: No need to manually `include` or `require` controller files. The framework uses the namespace to find the file in `class/Controller/`.
2.  **Inheritance**: Must extend `GaiaAlpha\Controller\BaseController`.
3.  **Helpers**:
    *   `$this->requireAuth()`: Enforces a logged-in session.
    *   `$this->jsonResponse($data, $status)`: Sends a JSON response and terminates execution.
    *   `$this->getJsonInput()`: Safely retrieves and decodes JSON request bodies.
4.  **MCP Integration**: Controller methods are the ideal backend for MCP Tools. By keeping your controller logic modular, you can easily expose it to AI agents via the MCP server.

## Checklist

- [x] Resides in `YourPlugin/class/Controller/`.
- [x] Uses the correct PSR-4 namespace.
- [x] Extends `BaseController`.
- [x] Routes are registered and follow the `/@/` prefix convention for API calls.
