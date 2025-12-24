import { ref, onMounted, computed } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
    <div class="audit-log-page">
        <div class="admin-header">
            <h2 class="page-title">
                <LucideIcon name="shield" size="32" />
                Audit Trail
            </h2>
            <div class="button-group">
                <button @click="loadLogs" class="btn btn-primary">
                    <LucideIcon name="refresh-cw" size="16" />
                    Refresh
                </button>
            </div>
        </div>

        <div class="admin-card">
            <div class="filters" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input v-model="filters.user_id" placeholder="User ID" class="form-input" style="width: 150px;" @keyup.enter="loadLogs">
                <input v-model="filters.action" placeholder="Action (e.g. POST)" class="form-input" @keyup.enter="loadLogs">
                <button @click="loadLogs" class="btn btn-secondary">Filter</button>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Method</th>
                        <th>Resource</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs" :key="log.id">
                        <td>{{ formatDate(log.created_at) }}</td>
                        <td>{{ log.user_id ? 'User ' + log.user_id : 'System' }}</td>
                        <td>
                            <span class="badge" :style="getActionStyle(log.action)">{{ log.action }}</span>
                        </td>
                        <td>{{ log.method }}</td>
                        <td>
                            <span v-if="log.resource_type">{{ log.resource_type }} #{{ log.resource_id }}</span>
                            <span v-else class="text-muted">-</span>
                        </td>
                        <td>
                            <button @click="viewDetails(log)" class="btn btn-sm btn-outline">View</button>
                        </td>
                    </tr>
                    <tr v-if="logs.length === 0">
                        <td colspan="6" class="text-center text-muted" style="padding: 20px;">No logs found.</td>
                    </tr>
                </tbody>
            </table>

            <div class="pagination" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                <span class="text-muted">Page {{ meta.page }} of {{ meta.pages }} ({{ meta.total }} records)</span>
                <div class="btn-group">
                    <button @click="changePage(meta.page - 1)" :disabled="meta.page <= 1" class="btn btn-sm">Prev</button>
                    <button @click="changePage(meta.page + 1)" :disabled="meta.page >= meta.pages" class="btn btn-sm">Next</button>
                </div>
            </div>
        </div>

        <!-- Details Modal (Simplified as fixed overlay for now) -->
        <div v-if="selectedLog" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
            <div class="admin-card" style="width: 800px; max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Log Details #{{ selectedLog.id }}</h3>
                    <button @click="selectedLog = null" class="btn btn-sm btn-danger">Close</button>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <strong>Action:</strong> {{ selectedLog.action }}<br>
                        <strong>Endpoint:</strong> {{ selectedLog.endpoint }}<br>
                        <strong>IP:</strong> {{ selectedLog.ip_address }}<br>
                        <strong>User Agent:</strong> {{ selectedLog.user_agent }}
                    </div>
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

                <h4>Payload</h4>
                <pre style="background: #f4f4f5; padding: 10px; border-radius: 4px; overflow-x: auto;">{{ formatJson(selectedLog.payload) }}</pre>

                <div v-if="selectedLog.old_value">
                    <h4>Old Value</h4>
                    <pre style="background: #fef2f2; padding: 10px; border-radius: 4px; overflow-x: auto;">{{ formatJson(selectedLog.old_value) }}</pre>
                </div>

                <div v-if="selectedLog.new_value">
                    <h4>New Value</h4>
                    <pre style="background: #f0fdf4; padding: 10px; border-radius: 4px; overflow-x: auto;">{{ formatJson(selectedLog.new_value) }}</pre>
                </div>
            </div>
        </div>
    </div>
    `,
    setup() {
        const logs = ref([]);
        const meta = ref({ page: 1, limit: 20, total: 0, pages: 1 });
        const loading = ref(false);
        const selectedLog = ref(null);
        const filters = ref({ user_id: '', action: '' });

        const loadLogs = async (page = 1) => {
            loading.value = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    limit: meta.value.limit,
                    user_id: filters.value.user_id,
                    action: filters.value.action
                });
                
                const res = await fetch('/api/audit-logs?' + params.toString());
                if (res.ok) {
                    const data = await res.json();
                    logs.value = data.data;
                    meta.value = data.meta;
                }
            } catch (err) {
                console.error("Failed to load logs:", err);
            } finally {
                loading.value = false;
            }
        };

        const changePage = (newPage) => {
            if (newPage > 0 && newPage <= meta.value.pages) {
                loadLogs(newPage);
            }
        };

        const viewDetails = (log) => {
            selectedLog.value = log;
        };

        const formatDate = (dateStr) => {
            return new Date(dateStr).toLocaleString();
        };

        const formatJson = (jsonStr) => {
            try {
                return JSON.stringify(JSON.parse(jsonStr), null, 2);
            } catch (e) {
                return jsonStr;
            }
        };

        const getActionStyle = (action) => {
            if (action.includes('DELETE')) return { background: '#fee2e2', color: '#991b1b' };
            if (action.includes('POST')) return { background: '#dcfce7', color: '#166534' };
            return { background: '#e0f2fe', color: '#075985' };
        };

        onMounted(() => {
            loadLogs();
        });

        return {
            logs,
            meta,
            loading,
            selectedLog,
            filters,
            loadLogs,
            changePage,
            viewDetails,
            formatDate,
            formatJson,
            getActionStyle
        };
    }
}
