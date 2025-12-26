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
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Session;

class YourController extends BaseController
{
    /**
     * Required: Register your routes here.
     */
    public function registerRoutes()
    {
        // Use /@/api/ for data endpoints
        \GaiaAlpha\Router::add('GET', '/@/api/todos', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/api/todos', [$this, 'create']);
        
        // Use /@/admin/ for administrative tools
        \GaiaAlpha\Router::add('GET', '/@/admin/stats', [$this, 'stats']);
    }

    public function index()
    {
        // IMPORTANT: Check return value and exit if not authenticated
        if (!$this->requireAuth()) return;
        
        $userId = Session::id();
        $items = YourService::getItems($userId);
        Response::json(['items' => $items]);
    }
    
    public function create()
    {
        // IMPORTANT: Check return value and exit if not authenticated
        if (!$this->requireAuth()) return;
        
        // Use the Request helper for standard input handling
        $input = Request::input(); 
        
        if (empty($input['name'])) {
            Response::json(['error' => 'Name required'], 400);
            return;  // IMPORTANT: Exit after error response
        }

        $userId = Session::id();
        $item = YourService::createItem($userId, $input);
        Response::json($item, 201);
    }
}
```

## Registration

Controllers in plugins must be registered via `GaiaAlpha\Framework::registerController` within a hook in `index.php`.

```php
// plugins/YourPlugin/index.php
Hook::add('framework_load_controllers_after', function () {
    \GaiaAlpha\Framework::registerController('your-key', \YourPlugin\Controller\YourController::class);
});
```

The `registerController` method handles:
1.  **Instantiation**: Creates a new instance of the controller class.
2.  **Initialization**: Calls the `init()` method if it exists.
3.  **Route Registration**: Calls the `registerRoutes()` method if it exists.
4.  **Global Availability**: Adds the controller to the system's central registry.

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

## Recommended Design Patterns

Controllers often become bloated. Apply these patterns to keep them lean:

1.  **Service Layer**: Controllers should not contain business logic. They should validate input, call a **Service**, and return a response.
    - Bad: Calculating tax and saving order details inside `create()`.
    - Good: Calling `OrderService::create($input)` inside `create()`.
2.  **Strategy**: If an endpoint behaves differently based on input (e.g., `payment_method: 'stripe'` vs `'paypal'`), defining a Strategy interface allows you to swap implementations without `if/else` spaghetti in the controller.
3.  **Command**: For complex actions (like "Publish Page"), encapsulate the logic in a Command class. The controller simply instantiates and executes the command.
 
```

## Security & Validation

Controllers are the gatekeepers of the system. Follow these security patterns:

1.  **Authentication**: Always check `requireAuth()` or `requireAdmin()` and **return** on failure.
2.  **Parameter Binding**: Use `?` placeholders in every SQL query. Never use string interpolation.
3.  **Typed Input**: Use specialized `Request` methods to enforce types:
    - `Request::queryInt($key, $default)`: Enforces integer from GET parameters.
    - `Request::input($key, $default)`: Safely gets value from JSON/POST.
4.  **Existence Validation**: Never assume an ID exists in the database.
    ```php
    $item = DB::fetch("SELECT id FROM items WHERE id = ?", [$id]);
    if (!$item) {
        Response::json(['error' => 'Not Found'], 404);
        return;
    }
    ```
5.  **Output Sanitization**: If data from a controller will be rendered into a PHP template (not just via Vue), use `htmlspecialchars()`.

## Common Pitfalls

### Standardized Route Prefixes

All system routes should follow the `/@/` convention:
- `/@/api/*`: Standardized JSON API endpoints.
- `/@/admin/*`: System administration.
- `/@/app/*`: User-facing specialized application dashboards.

Always use the fully qualified `\GaiaAlpha\Router::add()` to avoid namespace conflicts in plugins.
## Checklist

- [x] Resides in `YourPlugin/class/Controller/`.
- [x] Uses the correct PSR-4 namespace.
- [x] Extends `BaseController`.
- [x] Routes are registered and follow the `/@/` prefix convention for API calls.
- [x] Routes and API logic are documented in the plugin's documentation file.

## See Also

- [AssetController Pattern](../frontend/asset_controller.md): For details on serving static assets.
```
