import { ref, onMounted, computed } from 'vue';
import Icon from 'ui/Icon.js';
import { store } from 'store';

export default {
    components: { LucideIcon: Icon },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">
                    Plugins
                </h2>
                <div class="actions">
                    <button v-if="isDirty" class="btn btn-success" @click="saveChanges" :disabled="saving" style="margin-right: 10px;">
                        <LucideIcon name="save" size="18" style="display:inline-block; vertical-align:middle; margin-right:6px;"></LucideIcon>
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <button class="btn btn-primary" @click="showInstallModal = true">
                        <LucideIcon name="download" size="18" style="display:inline-block; vertical-align:middle; margin-right:6px;"></LucideIcon>
                        Install Plugin
                    </button>
                </div>
            </div>

            <div class="admin-card" style="margin-bottom: 20px; padding: 15px;">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <div style="flex: 1; position: relative;">
                        <LucideIcon name="search" size="16" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #666;"></LucideIcon>
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            placeholder="Search plugins..." 
                            class="form-control" 
                            style="padding-left: 32px;"
                        >
                    </div>
                    <div style="width: 200px;">
                        <select v-model="selectedCategory" class="form-control">
                            <option value="">All Categories</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div v-if="loading" class="admin-card">Loading plugins...</div>
            <div v-else-if="error" class="admin-card error">{{ error }}</div>
            
            <div v-else class="admin-card">
                <div v-if="filteredPlugins.length === 0" class="empty-state">
                    No plugins found.
                </div>
                <table v-else class="data-table">
                    <thead>
                        <tr>
                            <th>Plugin Name</th>
                            <th>Category</th>
                            <th style="width: 150px; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="plugin in filteredPlugins" :key="plugin.name">
                            <td>
                                <div class="plugin-name">
                                    {{ plugin.name }}
                                    <span v-if="plugin.is_system" class="badge badge-error">System</span>
                                    <span v-else-if="plugin.is_core" class="badge badge-info">Core</span>
                                    <span v-for="tag in plugin.tags" :key="tag" class="badge badge-tag">{{ tag }}</span>
                                </div>
                                <div v-if="plugin.requires && Object.keys(plugin.requires).length > 0" class="plugin-deps">
                                    <span class="deps-label">Requires:</span>
                                    <span v-for="(ver, dep) in plugin.requires" :key="dep" class="dep-tag" :class="{'dep-tag-core': dep === 'gaia-alpha'}">
                                        {{ dep }} <span class="dep-ver">{{ ver }}</span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="category-label">{{ plugin.category || 'Uncategorized' }}</span>
                            </td>
                            <td style="text-align: right;">
                                <label class="switch">
                                    <input 
                                        type="checkbox" 
                                        :checked="plugin.active" 
                                        :disabled="plugin.is_system"
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
                 <p><strong>Note:</strong> Plugins are loaded from <code>my-data/plugins/</code> and <code>plugins/</code>. Changes are saved to <code>my-data/active_plugins.json</code> only when you click Save.</p>
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
        const searchQuery = ref('');
        const selectedCategory = ref('');

        const categories = computed(() => {
            const cats = new Set();
            plugins.value.forEach(p => {
                if (p.category) cats.add(p.category);
            });
            return Array.from(cats).sort();
        });

        const filteredPlugins = computed(() => {
            return plugins.value.filter(p => {
                const matchesSearch = p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                    (p.tags && p.tags.some(t => t.toLowerCase().includes(searchQuery.value.toLowerCase())));
                const matchesCategory = !selectedCategory.value || p.category === selectedCategory.value;
                return matchesSearch && matchesCategory;
            });
        });

        // Install State
        const showInstallModal = ref(false);
        const installUrl = ref('');
        const isRawUrl = ref(false);
        const installing = ref(false);
        const installError = ref(null);

        const isDirty = ref(false);
        const saving = ref(false);

        const fetchPlugins = async () => {
            loading.value = true;
            try {
                const res = await fetch('/@/admin/plugins');
                if (!res.ok) throw new Error('Failed to load plugins');
                plugins.value = await res.json();
                isDirty.value = false;
            } catch (e) {
                error.value = e.message;
            } finally {
                loading.value = false;
            }
        };

        const togglePlugin = (plugin) => {
            plugin.active = !plugin.active;
            isDirty.value = true;
        };

        const saveChanges = async () => {
            if (!isDirty.value) return;
            saving.value = true;

            const activePlugins = plugins.value
                .filter(p => p.active)
                .map(p => p.name);

            try {
                const res = await fetch('/@/admin/plugins/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ active_plugins: activePlugins })
                });

                if (!res.ok) {
                    const data = await res.json();
                    throw new Error(data.error || 'Failed to save changes');
                }

                isDirty.value = false;
                store.addNotification('Plugins updated successfully! Reloading...', 'success');

                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } catch (e) {
                store.addNotification(e.message, 'error');
            } finally {
                saving.value = false;
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

                let data;
                try {
                    data = await res.json();
                } catch (e) {
                    throw new Error('Failed to parse response from server');
                }

                if (!res.ok) {
                    throw new Error(data.error || 'Installation failed');
                }

                store.addNotification('Plugin installed successfully!', 'success');
                showInstallModal.value = false;
                installUrl.value = '';
                isRawUrl.value = false;
                // Refresh list
                await fetchPlugins();
            } catch (e) {
                installError.value = e.message;
                store.addNotification(e.message, 'error');
            } finally {
                installing.value = false;
            }
        };

        onMounted(() => {
            fetchPlugins();

            // Inject Styles
            const styleId = 'plugins-admin-styles';
            if (!document.getElementById(styleId)) {
                const style = document.createElement('style');
                style.id = styleId;
                style.textContent = `
                    .plugin-name {
                        font-weight: 500;
                        font-size: 1.1em;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        flex-wrap: wrap;
                    }
                    .badge {
                        font-size: 0.7em;
                        padding: 2px 6px;
                        border-radius: 4px;
                        text-transform: uppercase;
                        font-weight: bold;
                    }
                    .badge-info {
                        background: rgba(99, 102, 241, 0.1);
                        color: #6366f1;
                        border: 1px solid rgba(99, 102, 241, 0.2);
                    }
                    .badge-tag {
                        background: #f1f5f9;
                        border: 1px solid #e2e8f0;
                        color: #64748b;
                        font-size: 0.65em;
                        font-weight: normal;
                        text-transform: none;
                    }
                    .category-label {
                        display: inline-block;
                        padding: 2px 8px;
                        border-radius: 12px;
                        background: #eef2f6;
                        color: #4b5563;
                        font-size: 0.8em;
                        font-weight: 500;
                    }
                    .plugin-deps {
                        font-size: 0.85em;
                        margin-top: 4px;
                        color: #64748b;
                    }
                    .deps-label {
                        margin-right: 4px;
                        font-size: 0.9em;
                        opacity: 0.7;
                    }
                    .dep-tag {
                        display: inline-flex;
                        align-items: center;
                        background: #f1f5f9;
                        padding: 1px 6px;
                        border-radius: 4px;
                        margin-right: 4px;
                        border: 1px solid #e2e8f0;
                        color: #475569;
                    }
                    .dep-tag-core {
                        background: #fff;
                        border-color: #e2e8f0;
                        opacity: 0.7;
                    }
                    .dep-ver {
                        font-size: 0.8em;
                        opacity: 0.6;
                        margin-left: 3px;
                    }
                    .badge-error {
                        background: rgba(239, 68, 68, 0.1);
                        color: #ef4444;
                        border: 1px solid rgba(239, 68, 68, 0.2);
                    }
                `;
                document.head.appendChild(style);
            }
        });

        return {
            plugins, loading, error, togglePlugin,
            showInstallModal, installUrl, isRawUrl, installing, installError, installPlugin,
            isDirty, saving, saveChanges,
            searchQuery, selectedCategory, categories, filteredPlugins
        };
    }
};
