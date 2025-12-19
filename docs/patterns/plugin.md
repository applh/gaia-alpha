
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

## Golden Sample: index.php

This file handles **Hook Registration** and **Menu Injection**. Note that specific feature logic should reside in dedicated classes to keep this file lightweight.

```php
<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use YourPlugin\Controller\YourController;

// 1. Dynamic Controller Registration
Hook::add('framework_load_controllers_after', function ($controllers) {
    // The framework handles autoloading YourController::class
    if (class_exists(YourController::class)) {
        $controller = new YourController();
        $controllers['your_plugin'] = $controller;
        Env::set('controllers', $controllers);
    }
});

// 2. Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'id' => 'grp-content', 
            'children' => [[
                'label' => 'Your Plugin', 
                'view' => 'your_plugin',
                'icon' => 'box',
                'path' => '/admin/your-plugin'
            ]]
        ];
    }
    return $data;
});
```

## Dynamic Feature Discovery (MCP)

Instead of hardcoding tools in `index.php`, the MCP server uses **Dynamic Discovery**. If you create a class in `plugins/YourPlugin/class/Tool/`, it will be automatically discovered if it extends the base tool class and follows the naming convention.

```php
// Standard pattern: keep index.php for "wiring" and classes for "doing".
```

## Checklist

- [x] Namespace matches folder: `plugins/Name` -> `namespace Name`
- [x] Classes reside in `class/` for PSR-4 autoloading.
- [x] `plugin.json` exists for metadata.
- [x] Uses Hooks to interact with the core without modifying it.
