# UI Component Pattern (Vue 3)

The Gaia Alpha frontend is built with **ES Modules** and **Vue 3**. This allows for a modular, build-less architecture where components are loaded dynamically by the browser.

## Architectural Principles

1.  **ES Module Modularity**: Each component is a standalone `.js` module. No bundling is required during development.
2.  **Explicit Dependencies**: Components use standard `import` statements. The browser resolves these via the **Import Map** defined in the main layout.
3.  **Dynamic Loading**: Views are loaded on demand by the frontend router, keeping the initial page load lightweight.

## Golden Sample: Admin Panel

```javascript
import { ref, onMounted, shallowRef } from 'vue';
import Icon from 'ui/Icon.js'; // Resolved via Import Map

export default {
    // 1. Register sub-components
    components: { LucideIcon: Icon },

    // 2. Template as a string (no compilation step required)
    template: `
    <div class="admin-page">
        <div class="admin-header">
            <h2 class="page-title">
                <LucideIcon name="box" size="32" />
                My Feature
            </h2>
            <div class="button-group">
                <button @click="refresh" class="btn btn-primary">Refresh</button>
            </div>
        </div>

        <div class="admin-card">
            <div v-if="loading">Loading...</div>
            <div v-else>
                <ul>
                    <li v-for="item in items" :key="item.id">
                        {{ item.name }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
    `,

    // 3. Logic using Composition API
    setup() {
        const items = ref([]);
        const loading = ref(false);

        const loadData = async () => {
            loading.value = true;
            try {
                const res = await fetch('/@/my-plugin/items');
                if (res.ok) {
                    items.value = await res.json();
                }
            } finally {
                loading.value = false;
            }
        };

        const refresh = () => {
            loadData();
        };

        onMounted(() => {
            loadData();
        });

        // Expose to template
        return {
            items,
            loading,
            refresh
        };
    }
};
```

## Key Rules

1.  **Imports**: Always use absolute-style imports mapped by the Import Map (e.g., `ui/Icon.js`). This ensures consistency even when files are moved.
2.  **No .vue files**: Components are `.js` files exporting an object. This allows for native browser execution without a build step.
3.  **Modularity**: Keep components focused. If a setup function gets too large, refactor logic into a separate "composable" file in `resources/js/composables/`.
4.  **Local Assets Only**: **Never use external CDNs** in component templates or logic. All scripts, fonts, and icons must be hosted locally in `resources/js/vendor/` and served via the `/min/` or `/assets/` route.

## Notification Pattern

Always provide feedback for user actions. Never use `alert()`. Use the system toast notification system via the global store.

```javascript
import { store } from '/min/js/store.js';

// Success feedback
store.addNotification("Changes saved!", "success");

// Error feedback
store.addNotification("Operation failed: " + errorMessage, "error");

// Duration is optional (defaults to 3s)
store.addNotification("Loading...", "info", 5000);
```

## Global Integration
    1.  **Plugins**: Register the component in `index.php` using `\GaiaAlpha\UiManager::registerComponent('view_key', 'plugins/Name/File.js', true)`.
        > [!IMPORTANT]
        > This registration is **mandatory** for any view that will be navigated to via the sidebar or frontend router.
    2.  **Core**: Register via static map in `resources/js/site.js` (legacy/core only).
    3.  **Menu**: Inject the menu item via `auth_session_data` hook.
        > [!WARNING]
        > The `view` key in the menu item MUST exactly match the `view_key` used in `registerComponent`.

## Documentation Requirement

Complex UI components **must** be documented to explain their purpose and state management. This can be included in the plugin's documentation. Documentation should cover:
1.  **State**: Key reactive variables and their roles.
2.  **Interactions**: Main user actions (clicks, form submissions).
3.  **API Integration**: Which endpoints the component communicates with.

## Recommended Design Patterns

1.  **Composite Pattern**: Build complex views from smaller, reusable atomic components (e.g., Buttons, Inputs, Cards). This encourages reusability.
    - *Example*: A `UserForm` component that uses `TextInput`, `SelectBox`, and `SubmitButton` components.
2.  **Container/Presentational Separation**:
    - **Container**: Handles data fetching and business logic (the "smart" component).
    - **Presentational**: Handles rendering and user interaction events (the "dumb" component).
    - *Benefits*: Logic is easier to test, and the visual component can be reused with different data sources.
3.  **Observer (Reactivity)**: Vue's reactivity system is an implementation of the Observer pattern. Utilize `computed` properties to automatically update the view when state changes, rather than manually updating the DOM.

## Checklist

- [x] Resides in `resources/js/`.
- [x] Exports a valid Vue 3 component object.
- [x] Uses `import` for dependencies.
- [x] Template is defined as a string or template literal.

- [x] UI states and interactions are documented.

## Debugging & Development

To improve the developer experience and get detailed validation warnings (e.g., for props), it is highly recommended to use the **Development Build** of Vue during local development.

### Vue Development vs. Production
-   **Development Build** (`vue.esm-browser.js`): Includes full warnings, prop validation, and internal checks. Useful for catching bugs early.
-   **Production Build** (`vue.esm-browser.prod.js`): Minified and stripped of development-only checks. Optimized for performance.

### Configuration
The Vue version is defined in the Import Map within `templates/app.php`. TO enable better debugging, ensure the map points to the non-prod version:

```php
// templates/app.php
"imports": {
    // Development (Recommended for local)
    "vue": "<?= \GaiaAlpha\Asset::url('/js/vendor/vue.esm-browser.js') ?>", 
    
    // Production (Use for deployment)
    // "vue": "<?= \GaiaAlpha\Asset::url('/js/vendor/vue.esm-browser.prod.js') ?>",
    ...
}
```

> [!TIP]
> If you see console errors like "Invalid prop: custom validator check failed", switching to the development build will often tell you exactly which component and prop is causing the issue.

