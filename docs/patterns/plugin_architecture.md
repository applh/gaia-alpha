# Plugin Architecture Pattern

Gaia Alpha uses a strict but flexible plugin architecture designed to keep the core lightweight while allowing extensive customization.

## Core Concepts

### 1. Structure
Every plugin resides in `plugins/{PluginName}/`. The folder name must match the plugin name (CamelCase).

```text
plugins/PluginName/
├── plugin.json       # Manifest (Required)
├── index.php         # Entry Point (Hooks)
├── class/            # PSR-4 Classes (Autoloaded)
│   ├── Controller/
│   ├── Model/
│   └── Service/
├── templates/        # PHP Templates
├── assets/           # CSS/JS (Symlinked)
└── docs/             # Documentation
```

### 2. Manifest (`plugin.json`)
This file defines the plugin's metadata and loading behavior.

```json
{
  "name": "MyPlugin",
  "description": "Adds awesome features.",
  "version": "1.0.0",
  "author": "Antigravity",
  "context": ["public", "admin", "api"],
  "is_system": false
}
```

#### New Fields matches:
- **`context`**: Array of contexts where this plugin should load.
    - `public`: Frontend requests.
    - `admin`: Requests starting with `/@/`.
    - `api`: JSON API requests.
- **`is_system`**: Boolean. If `true`, the plugin cannot be disabled via the UI. Use for critical infrastructure (e.g., `JwtAuth`).

### 3. Autoloading
Classes in `plugins/PluginName/class/` are automatically mapped to the namespace `GaiaAlpha\Plugin\PluginName\`.

- `plugins/MyPlugin/class/Controller/MyController.php`
- -> `GaiaAlpha\Plugin\MyPlugin\Controller\MyController`

### 4. Entry Point (`index.php`)
This file is included *only* if the plugin is active and the context matches. Use it to register hooks.

```php
<?php

use GaiaAlpha\Hook;

Hook::add('router_dispatch_before', function() {
    // Logic here
});
```

## Best Practices

1.  **Isolation**: Keep all logic within your plugin folder. Do not modify core files.
2.  **Context Optimization**: If your plugin only adds a dashboard widget, set `"context": ["admin"]`. It won't load on public pages, saving memory.
3.  **API Standardization**: Expose functionality via `Service` classes so other plugins can reuse it.

## Checklist

- [ ] Folder Name is CamelCase.
- [ ] `plugin.json` is valid.
- [ ] `index.php` registers hooks (no side effects on load).
- [ ] Classes follow PSR-4.
