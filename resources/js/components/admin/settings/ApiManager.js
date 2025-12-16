import Icon from 'ui/Icon.js';

export default {
    name: 'ApiManager',
    components: { LucideIcon: Icon },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <div style="display:flex; align-items:center; gap:20px;">
                    <h2 class="page-title">
                        <LucideIcon name="zap" size="32" style="display:inline-block; vertical-align:middle; margin-right:12px;"></LucideIcon>
                        API Builder
                    </h2>
                    <button @click="saveConfig" class="btn btn-primary" :disabled="loading">
                        <LucideIcon name="save" size="18" style="display:inline-block; vertical-align:middle; margin-right:6px;"></LucideIcon> 
                        {{ loading ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>

            <div v-if="error" class="alert alert-error">{{ error }}</div>
            <div v-if="success" class="alert alert-success">{{ success }}</div>

            <!-- Active APIs Section -->
            <h3 style="margin-bottom: 15px; border-bottom: 2px solid var(--accent-color); padding-bottom: 10px;">Active APIs</h3>
            <div class="admin-grid" style="margin-bottom: 40px;">
                <div v-if="activeApis.length === 0" style="grid-column: 1 / -1; padding: 20px; background: rgba(0,0,0,0.1); border-radius: 8px; text-align: center; color: #888;">
                    No active APIs found. Enable some below.
                </div>
                <div v-for="table in activeApis" :key="table.name" class="admin-card">
                    <div class="card-header">
                        <h3>{{ table.name }}</h3>
                        <label class="switch">
                            <input type="checkbox" v-model="table.config.enabled">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Access Level</label>
                            <select v-model="table.config.auth_level">
                                <option value="admin">Admin Only</option>
                                <option value="user">Authenticated Users</option>
                                <option value="public">Public</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Allowed Methods</label>
                            <div class="checkbox-group" style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin: 0;"><input type="checkbox" value="GET" v-model="table.config.methods" style="width: auto;"> READ (GET)</label>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin: 0;"><input type="checkbox" value="POST" v-model="table.config.methods" style="width: auto;"> CREATE (POST)</label>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin: 0;"><input type="checkbox" value="PUT" v-model="table.config.methods" style="width: auto;"> UPDATE (PUT)</label>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin: 0;"><input type="checkbox" value="DELETE" v-model="table.config.methods" style="width: auto;"> DELETE (DELETE)</label>
                            </div>
                        </div>
                        <div class="api-preview" style="margin-top: 16px; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 4px; font-family: monospace; font-size: 0.85rem;">
                            Endpoint: <span style="color: var(--accent-color);">/@/v1/{{ table.name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inactive APIs Section -->
            <h3 style="margin-bottom: 15px; border-bottom: 2px solid #555; padding-bottom: 10px; color: #aaa;">Inactive APIs</h3>
            <div class="admin-grid">
                 <div v-if="inactiveApis.length === 0" style="grid-column: 1 / -1; padding: 20px; background: rgba(0,0,0,0.1); border-radius: 8px; text-align: center; color: #888;">
                    All available tables have active APIs.
                </div>
                <div v-for="table in inactiveApis" :key="table.name" class="admin-card disabled" style="opacity: 0.7;">
                    <div class="card-header">
                        <h3>{{ table.name }}</h3>
                        <label class="switch">
                            <input type="checkbox" v-model="table.config.enabled">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <!-- Simplified body for inactive items, or same body but hidden/collapsed? -->
                    <!-- Keeping it visible so user can configure before enabling -->
                     <div class="card-body">
                         <p style="font-size: 0.9em; color: #888; font-style: italic;">
                            Enable this API to configure access and methods.
                         </p>
                    </div>
                </div>
            </div>
        </div>
    `,
    data() {
        return {
            tables: [],
            loading: false,
            error: null,
            success: null
        }
    },
    computed: {
        activeApis() {
            return this.tables.filter(t => t.config.enabled);
        },
        inactiveApis() {
            return this.tables.filter(t => !t.config.enabled);
        }
    },
    mounted() {
        this.fetchTables();
    },
    methods: {
        async fetchTables() {
            try {
                const response = await fetch('/@/admin/api-builder/tables');
                if (!response.ok) throw new Error('Failed to fetch tables');
                this.tables = await response.json();
            } catch (e) {
                this.error = e.message;
            }
        },
        async saveConfig() {
            this.loading = true;
            this.error = null;
            this.success = null;

            try {
                // Save specific configs one by one or bulk? 
                // The backend endpoint saves one config at a time based on current implementation:
                // "handleSaveConfig" takes {name, config}.
                // We should probably loop or update backend to accept bulk. 
                // For now, let's just save valid enabled configs or all configs. 
                // Wait, checking ApiBuilderController::handleSaveConfig...
                // It expects {name: '...', config: {...}}

                // Let's Iterate for now, simplest path for MVP.
                for (const table of this.tables) {
                    await fetch('/@/admin/api-builder/config', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            name: table.name,
                            config: table.config
                        })
                    });
                }

                this.success = 'Configuration saved successfully';
            } catch (e) {
                this.error = 'Failed to save configuration';
            } finally {
                this.loading = false;
            }
        }
    }
}
