
# Core Plugin Pattern

Plugins are the standard way to add features. They reside in `plugins/`.

## Directory Structure

```text
plugins/YourPlugin/
├── index.php                # Entry point, hooks, registration
├── plugin.json              # Meta data
├── class/
│   ├── Controller/
│   │   └── YourController.php
│   └── Model/
│       └── YourModel.php
└── resources/
    └── js/
        └── YourPanel.js
```

## Golden Sample: index.php

This file handles **Auto-loading**, **Controller Registration**, and **Menu Injection**.

```php
<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use YourPlugin\Controller\YourController;

// 1. Register Controller (runs when controllers are loaded)
Hook::add('framework_load_controllers_after', function ($controllers) {

    if (class_exists(YourController::class)) {
        $controller = new YourController();
        if (method_exists($controller, 'init')) {
            $controller->init();
        }

        // Register controller instance in the map
        $controllers['your_plugin'] = $controller;

        // Important: Update Env to persist the change
        Env::set('controllers', $controllers);
    }
});

// 2. Inject Menu Item (runs when user session data is built)
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        // Example: Add to "Content" group (grp-content) or create new
        $data['user']['menu_items'][] = [
            'id' => 'grp-content', 
            'children' => [
                [
                    'label' => 'Your Plugin', 
                    'view' => 'your_plugin', // matched by frontend router
                    'icon' => 'box', // Lucide icon name
                    'path' => '/admin/your-plugin'
                ]
            ]
        ];
    }
    return $data;
});

// 3. Expose MCP Tools (Optional)
// Allows AI agents to interact with your plugin's features.
Hook::add('mcp_tools', function ($result) {
    $result['tools'][] = [
        'name' => 'your_plugin_tool',
        'description' => 'Perform a specific action from Your Plugin',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer']
            ],
            'required' => ['id']
        ]
    ];
    return $result;
});

Hook::add('mcp_tool_call', function ($null, $name, $arguments) {
    if ($name === 'your_plugin_tool') {
        // ... logic (e.g., call a method in YourController) ...
        return ['content' => [['type' => 'text', 'text' => 'Success!']]];
    }
    return $null;
});
```

## Checklist

- [ ] Namespace matches folder: `plugins/Name` -> `namespace Name`
- [ ] `plugin.json` exists (even if minimal).
- [ ] Controller routes are mapped to `/@/...` for API calls.
- [ ] Frontend path is `/admin/...`.
