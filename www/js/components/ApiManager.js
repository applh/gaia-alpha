export default {
    name: 'ApiManager',
    template: `
        <div class="api-manager">
            <div class="header-actions">
                <h1>API Builder</h1>
                <button @click="saveConfig" class="btn btn-primary" :disabled="loading">
                    {{ loading ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>

            <div v-if="error" class="alert alert-error">{{ error }}</div>
            <div v-if="success" class="alert alert-success">{{ success }}</div>

            <div class="card-grid">
                <div v-for="table in tables" :key="table.name" class="card" :class="{ 'disabled': !table.config.enabled }">
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
                            <div class="checkbox-group">
                                <label><input type="checkbox" value="GET" v-model="table.config.methods"> READ (GET)</label>
                                <label><input type="checkbox" value="POST" v-model="table.config.methods"> CREATE (POST)</label>
                                <label><input type="checkbox" value="PUT" v-model="table.config.methods"> UPDATE (PUT)</label>
                                <label><input type="checkbox" value="DELETE" v-model="table.config.methods"> DELETE (DELETE)</label>
                            </div>
                        </div>
                        <div class="api-preview">
                            <small>Endpoint: <code>/api/v1/{{ table.name }}</code></small>
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
