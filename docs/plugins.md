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

#### Application Lifecycle
| Hook Name | Arguments | Description |
|-----------|-----------|-------------|
| `app_init` | None | Fired at the very beginning of the application (Web & CLI). |
| `plugins_loaded` | None | Fired immediately after all plugins have been loaded. |
| `app_boot` | None | Fired after plugins are loaded but before controllers/tasks are run. |
| `app_terminate` | None | Fired when the application shuts down. |

#### Tasks (Boot & Run loop)
| Hook Name | Arguments | Description |
|-----------|-----------|-------------|
| `app_task_before` | `$step`, `$task` | Fired before any framework task/step. |
| `app_task_after` | `$step`, `$task` | Fired after any framework task/step. |
| `app_task_before_{step}` | `$task` | Fired before a specific step (e.g., `app_task_before_step10`). |
| `app_task_after_{step}` | `$task` | Fired after a specific step. |

#### Response
| Hook Name | Arguments | Description |
|-----------|-----------|-------------|
| `response_json_before` | `$context` | Fired before JSON encoding. `$context` is `['data' => &$data, 'status' => &$status]`. |
| `response_send_before` | `$data`, `$status` | Fired just before sending the response headers and body. |

#### Routing & Controllers
| Hook Name | Arguments | Description |
|-----------|-----------|-------------|
| `router_dispatch_before` | `$method`, `$uri` | Fired before the router attempts to match a route. |
| `router_matched` | `$route`, `$matches` | Fired when a route is successfully matched. |
| `router_dispatch_after` | `$route`, `$matches` | Fired after the route handler is executed. |
| `router_404` | `$uri` | Fired when no route matches. |
| `controller_init` | `$controller`, `$key` | Fired in `Framework::loadControllers`. |

#### Public Pages
| Hook Name | Arguments | Description |
|-----------|-----------|-------------|
| `public_pages_index` | `$pages` (Filter) | Filter the list of public pages returned by index. |
| `public_page_show` | `$page`, `$slug` (Filter) | Filter the page data returned by show. |
| `public_page_render_head` | `$page` | Action hook inside the `<head>` tag of public page render. |
| `public_page_render_header` | `$page` | Action hook before the `<header>` element. |
| `public_page_render_footer` | `$page` | Action hook after the `<footer>` element. |
| `public_page_render_node` | `$html`, `$node` (Filter) | Filter the HTML output of a content node. |

### CLI Support

Plugins are fully supported in CLI mode. The `plugins_loaded` and `app_boot` hooks are triggered when running `php cli.php`.
