
# UI Component Pattern (Vue 3)

The framework uses Vue 3 with a mix of Options API wrapper and Composition API `setup()`. Components are loaded via ES Modules.

## Golden Sample: Admin Panel

```javascript
import { ref, onMounted, shallowRef } from 'vue';
import Icon from 'ui/Icon.js'; // Mapped import

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

1.  **Imports**: Use absolute-style imports mapped by the Import Map (e.g., `ui/Icon.js`, `composables/useSorting.js`).
2.  **No .vue files**: Components are `.js` files exporting an object.
3.  **Styles**: Scoped styles are not supported directly. Use standard BEM or utility classes defined in global CSS.
4.  **Icons**: Use `LucideIcon` wrapper.
