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
        // Use the Request helper for standard input handling
        $input = \GaiaAlpha\Request::input(); 
        
        if (empty($input['name'])) {
             \GaiaAlpha\Response::json(['error' => 'Name required'], 400);
        }

        // ... Logic ...
        \GaiaAlpha\Response::json(['success' => true]);
    }
}
```

## Key Features

1.  **Autoloading**: No need to manually `include` or `require` controller files. The framework uses the namespace to find the file in `class/Controller/`.
2.  **Inheritance**: Must extend `GaiaAlpha\Controller\BaseController`.
3.  **Helpers**:
    *   `$this->requireAuth()`: Enforces a logged-in session.
    *   `\GaiaAlpha\Response::json($data, $status)`: Sends a JSON response and terminates execution (Static method).
    *   `\GaiaAlpha\Request::input($key, $default)`: Safely retrieves inputs from JSON body or POST data (Static method).
    *   `\GaiaAlpha\File::readJson($path)`: Helper to read and decode JSON files.
    *   `\GaiaAlpha\File::writeJson($path, $data)`: Helper to encode and write JSON files.
4.  **MCP Integration**: Controller methods are the ideal backend for MCP Tools. By keeping your controller logic modular, you can easily expose it to AI agents via the MCP server.

## Documentation Requirement

Any new controller logic **must** be documented within the relevant plugin or feature documentation (e.g., `docs/plugins/YourPlugin.md`). Documentation should include:
1.  **Routes**: List of all registered routes and their methods.
2.  **Input/Output**: Describe expected JSON request bodies and response structures.
3.  **Authentication**: Specify whether `requireAuth` or `requireAdmin` is used.

## Checklist

- [x] Resides in `YourPlugin/class/Controller/`.
- [x] Uses the correct PSR-4 namespace.
- [x] Extends `BaseController`.
- [x] Routes are registered and follow the `/@/` prefix convention for API calls.
- [x] Routes and API logic are documented in the plugin's documentation file.
