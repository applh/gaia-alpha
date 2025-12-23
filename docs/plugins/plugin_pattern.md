
# Core Plugin Pattern

Plugins are the standard way to add features. They reside in `plugins/`. Gaia Alpha leverages **PSR-4 Autoloading** and **Dynamic Discovery** to ensure the system is modular, extensible, and "Zero-Build".

## Architectural Principles

1.  **PSR-4 Isolation**: Each plugin has its own namespace mapped to its `class/` directory. This allows the framework (and AI agents) to instantiate classes without manual `require` statements.
2.  **Dynamic Discovery**: Core systems (like CLI, MCP, and Controllers) scan plugin directories to discover new features automatically.
3.  **Zero-Build**: Adding a new class file to a plugin is enough to make it available to the system if it follows the naming conventions.

## Directory Structure

```text
plugins/YourPlugin/
├── index.php                # Entry point, hooks, registration
├── plugin.json              # Meta data
├── schema.sql               # Database schema (loaded on activation)
├── class/                   # Auto-loaded PSR-4 namespace: YourPlugin\
│   ├── Controller/
│   │   └── YourController.php
│   ├── Tool/                # Scanned for MCP Tools
│   └── Model/
│       └── YourModel.php
└── resources/
    └── js/
        └── YourPanel.js
```

## Database Schema

Plugins can define their own database tables using a standard SQL file.

- **File**: `plugins/YourPlugin/schema.sql`
- **Format**: Standard SQLite-compatible SQL (`CREATE TABLE IF NOT EXISTS ...`).
- **Activation**: When a plugin is activated via the Admin Panel, the framework automatically runs this SQL file to create tables.
- **Migration**: The framework tracks loaded schemas. Changes to `schema.sql` after activation are not automatically applied; you must handle migrations manually or re-activate the plugin (carefully).

## Golden Sample: index.php

This file handles **Hook Registration** and **Menu Injection**. Note that specific feature logic should reside in dedicated classes to keep this file lightweight.

```php
<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use YourPlugin\Controller\YourController;

// 1. Dynamic Controller Registration
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('your_plugin', YourController::class);
});

// 2. Register UI Component (Dynamic Loading)
\GaiaAlpha\UiManager::registerComponent('your_plugin', 'plugins/YourPlugin/YourPanel.js', true);

// 3. Inject Menu Item (Preferred for Groups/Icons)
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'id' => 'grp-tools', // Optional: Add to existing group
            'label' => 'Tools', 
            'icon' => 'pen-tool',
            'children' => [[
                'label' => 'Your Plugin', 
                'view' => 'your_plugin', // Maps to registered component key
                'icon' => 'box'
            ]]
        ];
    }
    return $data;
});
```

## Frontend View Registration
 
Plugins verify their UI components dynamically using `\GaiaAlpha\UiManager`. **Do not** modify `resources/js/site.js`.
 
### Registration in `index.php`
 
Add the following line to your plugin's `index.php`:
 
```php
// Register UI Component
\GaiaAlpha\UiManager::registerComponent(
    'your_plugin_view_id',           // The 'view' key used in your menu item
    'plugins/YourPlugin/YourPanel.js', // Path relative to root (virtual path mapped by AssetController)
    true                             // adminOnly? true = admin only, false = public
);
```
 
The framework automatically injects these components into the frontend configuration. The frontend router will dynamically load `YourPanel.js` when the user navigates to a menu item with `view: 'your_plugin_view_id'`.

## Dynamic Feature Discovery (MCP)

Instead of hardcoding tools in `index.php`, the MCP server uses **Dynamic Discovery**. If you create a class in `plugins/YourPlugin/class/Tool/`, it will be automatically discovered if it extends the base tool class and follows the naming convention.

```php
## Documentation Requirement

All plugins **must** be accompanied by a dedicated documentation file in `docs/plugins/YourPlugin.md`. This file should cover:
1.  **Objective**: What does the plugin solve?
2.  **Configuration**: Any `Env` variables or `plugin.json` settings.
3.  **Hooks**: Which core hooks does it listen to or trigger?
4.  **CLI/MCP**: List any added commands or tools.

Keeping this file updated as features are added is mandatory.

## Recommended Design Patterns

Structured code is easier to maintain. While plugins can be simple, complex ones should follow established patterns:

1.  **[Service Pattern](../patterns/service.md)**: Use a Service class to handle complex business logic, especially if it maintains state (like a connection) or is used by multiple controllers.
2.  **[Model Pattern](../patterns/model.md)**: Use Model classes to define data structures, separating data shape from business logic.
3.  **Facade**: If your plugin offers complex functionality to *other* plugins, expose a simple static Facade (e.g., `YourPlugin::doSomething()`) that delegates to your internal classes.
4.  **Observer**: The `Hook` system is effectively an Observer pattern. Use it to decouple your plugin's actions from core events (e.g., `Hook::add('user_login', ...)`).

## Complex Example: File Explorer

The `File Explorer` plugin demonstrates a complex integration involving multiple services, a unified controller, and a rich UI:

- **Services**: `FileExplorerService` (Real FS) and `VirtualFsService` (VFS).
- **Controller**: `FileExplorerController` handles 10+ API endpoints.
- **UI**: Assembled from `TreeView`, `FileEditor`, and `ImageEditor` components.
- **Assets**: Linked via `resources/js/FileExplorer.js` and registered in `index.php`.

This plugin serves as the current "Gold Standard" for internal tool development in Gaia Alpha.

## Checklist

- [x] Namespace matches folder: `plugins/Name` -> `namespace Name`
- [x] Classes reside in `class/` for PSR-4 autoloading.
- [x] `plugin.json` exists for metadata.
- [x] Uses Hooks to interact with the core without modifying it.
- [x] Documentation exists and is updated in `docs/plugins/YourPlugin.md`.
