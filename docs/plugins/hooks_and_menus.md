# Plugin Hooks & Menus

Gaia Alpha provides a Hook system for plugins to interact with the core lifecycle and UI.

## Lifecycle Hooks

Plugins can listen to activation and deactivation events. These are useful for setting up default data, clearing caches, or other initialization tasks. Note that database tables (`schema.sql`) are automatically handled by the framework on activation.

### `plugin_activated`
Fired when a plugin is enabled in the Admin Panel.
- **Payload**: `(string) $pluginName`

### `plugin_deactivated`
Fired when a plugin is disabled.
- **Payload**: `(string) $pluginName`

```php
Hook::add('plugin_activated', function($pluginName) {
    if ($pluginName === 'MyPlugin') {
        // Run first-time setup
    }
});
```

## Menu Hooks

Gaia Alpha allows plugins to dynamically inject items into the main navigation menu using the Hook system. This ensures that menu items appear only when your plugin is active and the user has the appropriate permissions.

# Plugin Menu Hook System

Gaia Alpha allows plugins to dynamically inject items into the main navigation menu. There are now two ways to do this:

1. **Declarative (Recommended for simple items):** Define menu items in `plugin.json`.
2. **Programmatic (Advanced):** Use the `auth_session_data` hook in `index.php`.

## 1. Declarative Configuration

You can now define your menu items directly in your `plugin.json` file. The framework will automatically handle the registration and permission checks.

```json
{
    "name": "MyPlugin",
    "menu": {
        "items": [
            {
                "label": "My Plugin",
                "view": "my-plugin-view",
                "icon": "puzzle",
                "group": "grp-system", 
                "adminOnly": true
            }
        ]
    }
}
```

### Configuration Options

| Property | Type | Description |
|----------|------|-------------|
| `label` | string | The text to display in the menu. |
| `view` | string | The view identifier. |
| `icon` | string | Name of the [Lucide icon](https://lucide.dev/icons). |
| `group` | string | (Optional) The ID of the group to add this item to (e.g., `grp-content`, `grp-system`). |
| `adminOnly`| boolean| (Optional) If true, only shows for users with level >= 100. |

---

## 2. Programmatic Method (Hooks)

For dynamic logic, custom permission checks, or complex nesting, use the Hook system.

When a user logs in or their session is validated, the `AuthController` triggers the `auth_session_data` hook. Plugins can listen to this hook to modify the user session data.

### Basic Usage

Register a listener for the `auth_session_data` hook in your plugin's `index.php` file.

```php
<?php

use GaiaAlpha\Hook;

Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'label' => 'My Plugin',
            'view' => 'my-plugin-view',
            'icon' => 'puzzle' // Lucide icon name
        ];
    }
    return $data;
});
```

## Advanced Scenarios

### 1. Adding to an Existing Group

You can add items to existing menu groups by targeting their `id`.

**Common Group IDs:**
- `grp-projects` (Projects)
- `grp-content` (Content)
- `grp-system` (System)

```php
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'id' => 'grp-content', // Target the Content group
            'children' => [
                [
                    'label' => 'My Content Tool', 
                    'view' => 'my-tool', 
                    'icon' => 'file-text'
                ]
            ]
        ];
    }
    return $data;
});
```

### 2. Creating a New Top-Level Group

If your plugin needs its own section, you can create a new group.

```php
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'label' => 'Analytics',
            'icon' => 'bar-chart',
            'id' => 'grp-analytics',
            'children' => [
                ['label' => 'Dashboard', 'view' => 'analytics-dash', 'icon' => 'pie-chart'],
                ['label' => 'Reports', 'view' => 'analytics-reports', 'icon' => 'file-text']
            ]
        ];
    }
    return $data;
});
```

### 3. Restricting to Admins

Always check user permissions before adding sensitive menu items.

```php
Hook::add('auth_session_data', function ($data) {
    // Check if user exists and has admin level (>= 100)
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        $data['user']['menu_items'][] = [
            'label' => 'System',
            'id' => 'grp-system',
            'children' => [
                ['label' => 'My Admin Tool', 'view' => 'my-admin-tool', 'icon' => 'shield']
            ]
        ];
    }
    return $data;
});
```

## Menu Item Reference

A menu item object can have the following properties:

| Property | Type | Description |
|----------|------|-------------|
| `label` | string | The text to display in the menu. |
| `view` | string | The view identifier. Should match a route or component name. Required for leaf nodes. |
| `icon` | string | Name of the [Lucide icon](https://lucide.dev/icons) to display. |
| `id` | string | Unique identifier for groups (required for grouping/merging). |
| `children` | array | Array of menu item objects. If present, this item becomes a dropdown group. |
| `adminOnly`| boolean| If true, the frontend will also check for admin privileges (extra safety). |

## Best Practices

1. **Check Permissions**: Always verify `$data['user']['level']` if your feature is restricted.
2. **Use Existing Groups**: Try to integrate into `Projects`, `Content`, or `System` before creating new top-level groups to keep the UI clean.
3. **Unique View Names**: namespace your view names (e.g., `pluginname-viewname`) to avoid conflicts.
4. **Don't Overwrite**: Always append to `$data['user']['menu_items'][]`, never overwrite the entire array.
5. **Return Data**: **Crucial!** You must return the `$data` array, or you will break the login chain.
