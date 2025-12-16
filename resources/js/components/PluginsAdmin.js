import { ref, onMounted } from 'vue';
import Icon from './Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">
                    Plugins
                </h2>
                <div class="actions">
                    <button class="btn btn-primary" @click="showInstallModal = true">
                        <LucideIcon name="download" size="18" style="display:inline-block; vertical-align:middle; margin-right:6px;"></LucideIcon>
                        Install Plugin
                    </button>
                </div>
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

            <!-- Install Modal -->
            <div v-if="showInstallModal" class="modal-overlay" @click.self="showInstallModal = false">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Install Plugin</h3>
                        <button class="close-btn" @click="showInstallModal = false">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Plugin URL</label>
                            <input type="text" v-model="installUrl" :placeholder="isRawUrl ? 'https://example.com/plugin.zip' : 'https://github.com/user/my-plugin'" class="form-control">
                            
                            <div style="margin-top: 8px;">
                                <label style="display:flex; align-items:center; gap:8px; font-weight:normal; font-size:0.9em;">
                                    <input type="checkbox" v-model="isRawUrl"> 
                                    <span>Direct ZIP URL (Do not auto-format GitHub links)</span>
                                </label>
                            </div>

                            <small v-if="!isRawUrl">Enter a GitHub repository URL. We'll automatically fetch the latest code.</small>
                            <small v-else>Enter the direct link to a ZIP file containing the plugin.</small>
                        </div>
                        <div v-if="installError" class="alert alert-error">{{ installError }}</div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="showInstallModal = false" :disabled="installing">Cancel</button>
                        <button class="btn btn-primary" @click="installPlugin" :disabled="installing || !installUrl">
                            {{ installing ? 'Installing...' : 'Install' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup() {
        const plugins = ref([]);
        const loading = ref(true);
        const error = ref(null);

        // Install State
        const showInstallModal = ref(false);
        const installUrl = ref('');
        const isRawUrl = ref(false);
        const installing = ref(false);
        const installError = ref(null);

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

        const installPlugin = async () => {
            if (!installUrl.value) return;
            installing.value = true;
            installError.value = null;

            try {
                const res = await fetch('/@/admin/plugins/install', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        url: installUrl.value,
                        is_raw: isRawUrl.value
                    })
                });

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.error || 'Installation failed');
                }

                alert('Plugin installed successfully!');
                showInstallModal.value = false;
                installUrl.value = '';
                isRawUrl.value = false;
                // Refresh list
                await fetchPlugins();
            } catch (e) {
                installError.value = e.message;
            } finally {
                installing.value = false;
            }
        };

        onMounted(fetchPlugins);

        return {
            plugins, loading, error, togglePlugin,
            showInstallModal, installUrl, isRawUrl, installing, installError, installPlugin
        };
    },
    styles: `

        .plugin-name {
            font-weight: 500;
            font-size: 1.1em;
        }
    `
};
