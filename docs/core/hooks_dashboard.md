# Dashboard Hooks

## `dashboard_widgets`

Allows plugins to register custom Vue components to be displayed on the Admin Dashboard.

### Usage

```php
\GaiaAlpha\Hook::add('dashboard_widgets', function($widgets) {
    $widgets[] = [
        'name' => 'MyPluginWidget',
        'path' => 'plugins/MyPlugin/resources/js/MyWidget.js',
        'width' => 'full' // Options: 'full' (default), 'half', 'third'
    ];
    return $widgets;
});
```

### Widget Component

The Javascript file should export a Vue component.

```javascript
export default {
    template: `
        <div class="card">
            <h3>My Widget</h3>
            <p>Content goes here...</p>
        </div>
    `
}
```
