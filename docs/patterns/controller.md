
# Controller Pattern

This pattern is based on `GaiaAlpha\Controller\BaseController`.

## Golden Sample

```php
<?php

namespace YourPlugin\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;

class YourController extends BaseController
{
    /**
     * Optional: Hook for initialization logic.
     * Called by index.php immediately after instantiation.
     */
    public function init()
    {
        // ...
    }

    /**
     * Required: Register your routes here.
     * This method is called by the framework during the controller loading phase (if manually invoked in index.php).
     */
    public function registerRoutes()
    {
        // Public Route
        Router::add('GET', '/@/your-plugin/items', [$this, 'index']);
        
        // Secured Route (POST)
        Router::add('POST', '/@/your-plugin/items', [$this, 'create']);
        
        // Dynamic Route
        Router::add('POST', '/@/your-plugin/items/(\d+)', [$this, 'update']);
    }

    public function index()
    {
        $this->requireAuth(); // Enforce authentication
        
        // ... Logic using Models ...
        $data = ['items' => []];

        $this->jsonResponse($data);
    }
    
    public function create()
    {
        $this->requireAuth();
        
        // Get JSON body automatically
        $input = $this->getJsonInput();
        
        if (empty($input['name'])) {
            $this->jsonResponse(['error' => 'Name required'], 400);
        }

        // ... Logic ...

        $this->jsonResponse(['success' => true]);
    }
}
```

## Key Features

1.  **Inheritance**: Must extend `GaiaAlpha\Controller\BaseController`.
2.  **Routing**: Routes are defined in `registerRoutes()`. Note that in `index.php`, you usually have to manually instantiate the controller and call `registerRoutes()` or let the framework do it if it detects the method.
3.  **Helpers**:
    *   `$this->requireAuth()`: returns 401 if not logged in.
    *   `$this->requireAdmin()`: returns 403 if not admin.
    *   `$this->jsonResponse($data, $status)`: Sends JSON headers and exits.
    *   `$this->getJsonInput()`: Decodes `php://input` JSON.
