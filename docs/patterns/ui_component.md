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
4.  **Global Integration**: Plugins register their main views via the `auth_session_data` hook in PHP, which the frontend router then uses to dynamically load the corresponding module.

## Checklist

- [x] Resides in `resources/js/`.
- [x] Exports a valid Vue 3 component object.
- [x] Uses `import` for dependencies.
- [x] Template is defined as a string or template literal.
