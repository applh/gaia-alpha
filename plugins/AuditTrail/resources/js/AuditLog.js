import { ref, onMounted, computed } from 'vue';
import Icon from 'ui/Icon.js';
import DataTable from 'ui/DataTable.js';
import Pagination from 'ui/Pagination.js';
import Modal from 'ui/Modal.js';
import Input from 'ui/Input.js';
import UIButton from 'ui/Button.js';

export default {
    components: {
        LucideIcon: Icon,
        'ui-data-table': DataTable,
        'ui-pagination': Pagination,
        'ui-modal': Modal,
        'ui-input': Input,
        'ui-button': UIButton
    },
    template: `
    <div class="audit-log-page">
        <div class="admin-header">
            <h2 class="page-title">
                <LucideIcon name="shield" size="32" />
                Audit Trail
            </h2>
            <div class="button-group">
                <ui-button variant="primary" @click="loadLogs">
                    <LucideIcon name="refresh-cw" size="16" />
                    Refresh
                </ui-button>
            </div>
        </div>

        <div class="admin-card">
            <div class="filters" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <ui-input v-model="filters.user_id" placeholder="User ID" style="width: 150px;" @keyup.enter="loadLogs" />
                <ui-input v-model="filters.action" placeholder="Action (e.g. POST)" @keyup.enter="loadLogs" />
                <ui-button @click="loadLogs">Filter</ui-button>
            </div>

            <ui-data-table 
                :data="logs" 
                :columns="columns"
            >
                <template #col-created_at="{ row }">
                    {{ formatDate(row.created_at) }}
                </template>
                <template #col-user_id="{ row }">
                    {{ row.user_id ? 'User ' + row.user_id : 'System' }}
                </template>
                <template #col-action="{ row }">
                    <span class="badge" :style="getActionStyle(row.action)">{{ row.action }}</span>
                </template>
                <template #col-resource="{ row }">
                    <span v-if="row.resource_type">{{ row.resource_type }} #{{ row.resource_id }}</span>
                    <span v-else class="text-muted">-</span>
                </template>
                <template #col-actions="{ row }">
                    <ui-button size="sm" @click="viewDetails(row)">View</ui-button>
                </template>
            </ui-data-table>

            <div class="pagination-container" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                <span class="text-muted">Total: {{ meta.total }} records</span>
                <ui-pagination 
                    v-model:currentPage="meta.page"
                    :total="meta.total"
                    :page-size="meta.limit"
                    @current-change="loadLogs"
                />
            </div>
        </div>

        <!-- Details Modal -->
        <ui-modal 
            v-model="modalVisible" 
            :title="'Log Details #' + (selectedLog?.id || '')"
            size="large"
        >
            <div v-if="selectedLog">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <strong>Action:</strong> {{ selectedLog.action }}<br>
                        <strong>Endpoint:</strong> {{ selectedLog.endpoint }}<br>
                        <strong>IP:</strong> {{ selectedLog.ip_address }}<br>
                        <strong>User Agent:</strong> {{ selectedLog.user_agent }}
                    </div>
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border-color);">

                <div style="margin-bottom: 24px;">
                    <h4 style="margin-bottom: 12px;">Payload</h4>
                    <pre style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: var(--radius-sm); overflow-x: auto; font-size: 0.85rem;">{{ formatJson(selectedLog.payload) }}</pre>
                </div>

                <div v-if="selectedLog.old_value" style="margin-bottom: 24px;">
                    <h4 style="margin-bottom: 12px; color: var(--danger-color);">Old Value</h4>
                    <pre style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.1); padding: 12px; border-radius: var(--radius-sm); overflow-x: auto; font-size: 0.85rem;">{{ formatJson(selectedLog.old_value) }}</pre>
                </div>

                <div v-if="selectedLog.new_value">
                    <h4 style="margin-bottom: 12px; color: var(--success-color);">New Value</h4>
                    <pre style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.1); padding: 12px; border-radius: var(--radius-sm); overflow-x: auto; font-size: 0.85rem;">{{ formatJson(selectedLog.new_value) }}</pre>
                </div>
            </div>
            <template #footer>
                <ui-button @click="modalVisible = false">Close</ui-button>
            </template>
        </ui-modal>
    </div>
    `,
    setup() {
        const logs = ref([]);
        const meta = ref({ page: 1, limit: 20, total: 0, pages: 1 });
        const loading = ref(false);
        const selectedLog = ref(null);
        const modalVisible = ref(false);
        const filters = ref({ user_id: '', action: '' });

        const columns = [
            { label: 'Date', prop: 'created_at', width: '200px' },
            { label: 'User', prop: 'user_id' },
            { label: 'Action', prop: 'action' },
            { label: 'Method', prop: 'method', width: '100px' },
            { label: 'Resource', prop: 'resource' },
            { label: 'Details', prop: 'actions', align: 'center', width: '100px' }
        ];

        const loadLogs = async (page = 1) => {
            // Handle pagination component sending page via event or using meta.page
            const targetPage = typeof page === 'number' ? page : meta.value.page;

            loading.value = true;
            try {
                const params = new URLSearchParams({
                    page: targetPage,
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

        const viewDetails = (log) => {
            selectedLog.value = log;
            modalVisible.value = true;
        };

        const formatDate = (dateStr) => {
            return new Date(dateStr).toLocaleString();
        };

        const formatJson = (jsonStr) => {
            if (!jsonStr) return '-';
            try {
                return JSON.stringify(JSON.parse(jsonStr), null, 2);
            } catch (e) {
                return jsonStr;
            }
        };

        const getActionStyle = (action) => {
            if (action.includes('DELETE')) return { background: 'rgba(239, 68, 68, 0.1)', color: '#fca5a5', border: '1px solid rgba(239, 68, 68, 0.2)' };
            if (action.includes('POST') || action.includes('CREATE')) return { background: 'rgba(16, 185, 129, 0.1)', color: '#6ee7b7', border: '1px solid rgba(16, 185, 129, 0.2)' };
            return { background: 'rgba(59, 130, 246, 0.1)', color: '#93c5fd', border: '1px solid rgba(59, 130, 246, 0.2)' };
        };

        onMounted(() => {
            loadLogs();
        });

        return {
            logs,
            meta,
            loading,
            selectedLog,
            modalVisible,
            filters,
            columns,
            loadLogs,
            viewDetails,
            formatDate,
            formatJson,
            getActionStyle
        };
    }
}

