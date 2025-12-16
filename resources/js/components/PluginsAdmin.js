import { ref, onMounted } from 'vue';
import Icon from './Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">
                    <LucideIcon name="plug" size="32" style="display:inline-block; vertical-align:middle; margin-right:12px;"></LucideIcon>
                    Plugins
                </h2>
            </div>

            <div v-if="loading" class="admin-card">Loading plugins...</div>
            <div v-else-if="error" class="admin-card error">{{ error }}</div>
            
            <div v-else class="admin-card">
                <div v-if="plugins.length === 0" class="empty-state">
                    No plugins found.
                </div>
                <table v-else class="data-table">
                    <thead>
                        <tr>
                            <th>Plugin Name</th>
                            <th style="width: 150px; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="plugin in plugins" :key="plugin.name">
                            <td>
                                <div class="plugin-name">{{ plugin.name }}</div>
                            </td>
                            <td style="text-align: right;">
                                <label class="switch">
                                    <input 
                                        type="checkbox" 
                                        :checked="plugin.active" 
                                        :disabled="plugin.processing"
                                        @change="togglePlugin(plugin)"
                                    >
                                    <span class="slider round"></span>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="admin-card" style="margin-top: 20px; font-size: 0.9em; color: #666;">
                 <p><strong>Note:</strong> Plugins are loaded from <code>my-data/plugins/</code>. When you deactivate a plugin, it is added to the exclusion list. If you delete <code>my-data/active_plugins.json</code>, all plugins will be reactivated.</p>
            </div>
        </div>
    `,
    setup() {
        const plugins = ref([]);
        const loading = ref(true);
        const error = ref(null);

        const fetchPlugins = async () => {
            loading.value = true;
            try {
                const res = await fetch('/@/admin/plugins');
                if (!res.ok) throw new Error('Failed to load plugins');
                plugins.value = await res.json();
            } catch (e) {
                error.value = e.message;
            } finally {
                loading.value = false;
            }
        };

        const togglePlugin = async (plugin) => {
            if (plugin.processing) return;
            plugin.processing = true;

            try {
                const res = await fetch('/@/admin/plugins/toggle', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: plugin.name,
                        active: !plugin.active
                    })
                });

                if (!res.ok) throw new Error('Failed to toggle plugin');

                const data = await res.json();
                plugin.active = data.active;
            } catch (e) {
                alert(e.message);
            } finally {
                plugin.processing = false;
            }
        };

        onMounted(fetchPlugins);

        return { plugins, loading, error, togglePlugin };
    },
    styles: `

        .plugin-name {
            font-weight: 500;
            font-size: 1.1em;
        }
    `
};
