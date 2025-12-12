import Icon from './Icon.js';

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

            <div class="admin-grid">
                <div v-for="table in tables" :key="table.name" class="admin-card" :class="{ 'disabled': !table.config.enabled }">
                    <div class="card-header">
                        <h3>{{ table.name }}</h3>
                        <label class="switch">
                            <input type="checkbox" v-model="table.config.enabled">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="card-body" v-if="table.config.enabled">
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
                            Endpoint: <span style="color: var(--accent-color);">/api/v1/{{ table.name }}</span>
                        </div>
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
    mounted() {
        this.fetchTables();
    },
    methods: {
        async fetchTables() {
            try {
                const response = await fetch('/api/admin/api-builder/tables');
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
                    await fetch('/api/admin/api-builder/config', {
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
