# Middleware & Pipeline

Gaia Alpha uses a robust **Pipeline** pattern (often called "Onion Architecture") to handle request processing. This allows you to wrap the application's core logic with layers of behavior, such as authentication, logging, or CORS handling.

## The Conccept

Think of the request as passing through layers of an onion.
1.  **Request** enters the outer layer (Middleware 1).
2.  Middleware 1 does work, then calls `$next($request)`.
3.  **Request** passes to the next layer (Middleware 2).
4.  ... eventually reaching the **Core Application** (Controller).
5.  **Response** returns back up through the layers in reverse order.

## Interface

All middleware must implement the `GaiaAlpha\Middleware` interface:

```php
namespace GaiaAlpha;

interface Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure $next The next middleware or handler in the pipeline.
     * @return mixed
     */
    public function handle(\Closure $next);
}
```

## Creating Middleware

Create a class in your plugin or core library that implements the interface.

**Example: `plugins/MyPlugin/class/LogMiddleware.php`**

```php
namespace MyPlugin;

use GaiaAlpha\Middleware;

class LogMiddleware implements Middleware
{
    public function handle(\Closure $next)
    {
        // Pre-processing
        error_log("Request Started");

        // Pass to next layer
        $response = $next();

        // Post-processing
        error_log("Request Finished");

        return $response;
    }
}
```

## Using the Pipeline

You can use the `GaiaAlpha\Pipeline` class to construct and execute a stack of middleware.

```php
use GaiaAlpha\Pipeline;

$pipeline = new Pipeline();

$result = $pipeline
    ->send([
        \MyPlugin\LogMiddleware::class,
        \GaiaAlpha\Middleware\AuthMiddleware::class,
    ])
    ->then(function() {
        // This is the "Core" destination
        return "Final Result";
    });
```

### Automatic Instantiation

The `Pipeline` class supports both instantiated objects and class name strings. If you pass a class name string, it will automatically instantiate it (assuming a parameterless constructor).

## Integration with Framework

Currently, the Framework does not automatically wrap every route in a global pipeline. Instead, middleware usage is often **situational** or implemented within specific **Workflow** handlers or custom **Runners**.

To implement a global middleware stack, you would typically intervene at the `app_boot` or `router_dispatch` hooks to wrap the final execution.
