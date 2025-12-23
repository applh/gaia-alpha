# AssetController Pattern

The `AssetController` is responsible for serving static assets (CSS, JS) from `resources/` and `plugins/` directories, providing minification, caching, and correct content-type headers.

## Why use AssetController?

1.  **Security**: It prevents directory traversal and ensures only allowed file types are served.
2.  **Performance**: It automatically minifies (simple whitespace removal) and caches assets in `my-data/cache/min/`.
3.  **Convenience**: It provides consistent routes for accessing vendor libraries and plugin assets without needing direct web root access or complicated symlinks.

## Standard Routes

The `AssetController` registers the following routes:

### 1. Vendor Libraries
**Route:** `/min/js/vendor/(.+)`
**Maps to:** `resources/js/vendor/`

**Usage:**
```html
<script src="/min/js/vendor/chart.js"></script>
```
```javascript
// Dynamic Import
await import('/min/js/vendor/chart.js');
```

### 2. General JavaScript
**Route:** `/min/js/(.+)`
**Maps to:** `resources/js/`

**Usage:**
```html
<script src="/min/js/app.js"></script>
```

### 3. CSS Files
**Route:** `/min/css/(.+)`
**Maps to:** `resources/css/`

**Usage:**
```html
<link rel="stylesheet" href="/min/css/style.css">
```

### 4. Plugin Assets
**Route:** `/min/js/(.+)` (when path starts with `plugins/`)

**Maps to:**
1.  `plugins/{PluginName}/{Path}` (Plugin Root)
2.  `plugins/{PluginName}/resources/js/{Path}` (Plugin Resources)

**Usage:**
```javascript
// Accessing file at plugins/MyPlugin/MyComponent.js
import MyComponent from '/min/js/plugins/MyPlugin/MyComponent.js';
```

## Best Practices

### 1. Use Absolute Paths for Imports
Always use the `/min/js/` prefix for imports within your JavaScript modules. This ensures that even if you move your files, the imports remain valid as long as the target file exists in the expected location relative to the project root.

**❌ BAD:**
```javascript
import SubComponent from './components/SubComponent.js';
```

**✅ GOOD:**
```javascript
import SubComponent from '/min/js/plugins/MyPlugin/components/SubComponent.js';
```

### 2. Centralize Vendor Libraries
Do not bundle large libraries (like Chart.js, Vue, etc.) inside your plugin if they are already available in `resources/js/vendor/`. Use the shareable URL.

**❌ BAD:**
```javascript
// Importing local copy
import Chart from './vendor/chart.js';
```

**✅ GOOD:**
```javascript
// Importing centralized copy via AssetController
await import('/min/js/vendor/chart.js');
```

### 3. Handle Side-Effect Imports (UMD)
Some libraries (like Chart.js UMD builds) do not export modules but attach themselves to the `window` object.

**Pattern:**
```javascript
if (typeof window.Chart === 'undefined') {
    await import('/min/js/vendor/chart.js');
}
const chart = new window.Chart(ctx, ...);
```

### 4. Debugging 404s
If you get a 404 for an asset:
1.  Check if the file exists in `resources/js/`, `resources/css/`, or `plugins/`.
2.  Ensure you are using the correct route prefix (`/min/js/`, `/min/css/`).
3.  Remember `www` is the web root. Files outside `www` (like in `resources`) **must** be accessed via these routes, not directly (unless symlinked, which is discouraged).
