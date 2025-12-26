import { ref, onMounted, computed, watch } from 'vue';
import { api } from 'api';
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
                <ui-input v-model="userFilter" placeholder="User ID" style="width: 150px;" @keyup.enter="loadLogs" />
                <ui-input v-model="eventFilter" placeholder="Action (e.g. POST)" @keyup.enter="loadLogs" />
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
                <span class="text-muted">Total: {{ totalItems }} records</span>
                <ui-pagination 
                    v-model:currentPage="currentPage"
                    :total="totalItems"
                    :page-size="pageSize"
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
        const loading = ref(false);
        const selectedLog = ref(null);
        const modalVisible = ref(false);

        // Pagination and filtering state
        const currentPage = ref(1);
        const pageSize = ref(20);
        const totalItems = ref(0);
        const totalPages = ref(1);
        const userFilter = ref('');
        const eventFilter = ref('');
        const stats = ref({});

        const columns = [
            { label: 'Date', prop: 'created_at', width: '200px' },
            { label: 'User', prop: 'user_id' },
            { label: 'Action', prop: 'action' },
            { label: 'Method', prop: 'method', width: '100px' },
            { label: 'Resource', prop: 'resource' },
            { label: 'Details', prop: 'actions', align: 'center', width: '100px' }
        ];

        const loadLogs = async () => {
            loading.value = true;
            try {
                const queryParams = {
                    page: currentPage.value,
                    limit: pageSize.value,
                    user_id: userFilter.value,
                    action: eventFilter.value
                };

                const filteredParams = Object.fromEntries(
                    Object.entries(queryParams).filter(([, value]) => value !== '' && value !== null && value !== undefined)
                );

                const data = await api.get('audit-logs', { params: filteredParams });
                logs.value = data.logs;
                totalItems.value = data.total;
                totalPages.value = data.total_pages;
            } catch (err) {
                console.error("Failed to load audit logs", err);
            } finally {
                loading.value = false;
            }
        };

        const loadStats = async () => {
            try {
                stats.value = await api.get('audit-logs/stats');
            } catch (err) {
                console.error("Failed to load audit log stats", err);
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
            loadStats();
        });

        return {
            logs,
            loading,
            selectedLog,
            modalVisible,
            currentPage,
            pageSize,
            totalItems,
            totalPages,
            userFilter,
            eventFilter,
            stats,
            columns,
            loadLogs,
            viewDetails,
            formatDate,
            formatJson,
            getActionStyle
        };
    }
}

