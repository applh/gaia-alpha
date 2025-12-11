# Plugin and Hook System

Gaia Alpha supports a flexible plugin system that allows you to extend the framework without modifying the core code.

## Plugin Structure

Plugins are located in the `my-data/plugins` directory. Each plugin must be in its own subdirectory and contain an `index.php` file.

```
my-data/plugins/
├── my-awesome-plugin/
│   ├── index.php
│   └── other-logic.php
└── another-plugin/
    └── index.php
```

The framework automatically loads all `index.php` files found in immediate subdirectories of `my-data/plugins` during the application boot process (both Web and CLI).

## Class Autoloading

You can include classes in your plugin by placing them in a `class/` subdirectory. The framework uses a standard naming convention to autoload these classes.

**Convention:** `PluginName\ClassName` -> `my-data/plugins/PluginName/class/ClassName.php`

**Example:**

Directory Structure:
```
my-data/plugins/
└── MyAwesomePlugin/
    ├── index.php
    └── class/
        └── Utils/
            └── Helper.php
```

File `class/Utils/Helper.php`:
```php
namespace MyAwesomePlugin\Utils;

class Helper {
    public static function sayHello() {
        return "Hello!";
    }
}
```

Usage in `index.php`:
```php
use MyAwesomePlugin\Utils\Helper;

echo Helper::sayHello();
```

## Hooks

The framework provides a `Hook` system that allows plugins to execute code at specific points in the application lifecycle.

### Usage

**Registering a Hook:**

```php
use GaiaAlpha\Hook;

Hook::add('hook_name', function($arg1, $arg2) {
    // Your code here
});
```

**Triggering a Hook (for your own custom events):**

```php
use GaiaAlpha\Hook;

Hook::run('my_custom_event', $data);
```

### Available Hooks

| Hook Name | Arguments | Description |
|-----------|-----------|-------------|
| `plugins_loaded` | None | Fired immediately after all plugins have been loaded. |
| `app_boot` | None | Fired after plugins are loaded but before controllers are initialized. |
| `controller_init` | `$controller`, `$key` | Fired when a controller is instantiated and initialized. |
| `router_dispatch_before` | `$method`, `$uri` | Fired before the router attempts to match a route. |
| `router_matched` | `$route`, `$matches` | Fired when a route is successfully matched, before the handler is called. |
| `router_404` | `$uri` | Fired when no route matches the request. |

### CLI Support

Plugins are fully supported in CLI mode. The `plugins_loaded` and `app_boot` hooks are triggered when running `php cli.php`.
