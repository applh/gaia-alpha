import { ref, onMounted, computed, watch, h } from 'vue';
import { useSorting } from 'composables/useSorting.js';
import { store } from 'store';
import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Container from 'ui/Container.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import Modal from 'ui/Modal.js';
import Input from 'ui/Input.js';
import Textarea from 'ui/Textarea.js';
import DataTable from 'ui/DataTable.js';
import { UITitle, UIText } from 'ui/Typography.js';
import Divider from 'ui/Divider.js';

export default {
    components: {
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-container': Container,
        'ui-row': Row,
        'ui-col': Col,
        'ui-modal': Modal,
        'ui-input': Input,
        'ui-textarea': Textarea,
        'ui-data-table': DataTable,
        'ui-title': UITitle,
        'ui-text': UIText,
        'ui-divider': Divider
    },
    template: `
        <ui-container class="database-manager">
            <div class="admin-header" style="margin-bottom: 32px;">
                <ui-title :level="1">
                    <LucideIcon name="database" size="28" style="margin-right: 12px; vertical-align: middle; color: var(--accent-color);" />
                    Database Manager
                </ui-title>
            </div>
            
            <ui-row :gutter="20">
                <!-- SQL Query Executor -->
                <ui-col :span="24">
                    <ui-card style="margin-bottom: 24px;">
                        <ui-title :level="3" style="margin-bottom: 16px;">SQL Query Executor</ui-title>
                        <ui-textarea 
                            v-model="sqlQuery" 
                            placeholder="Enter SQL query here (e.g., SELECT * FROM cms_users)..."
                            :rows="6"
                            style="margin-bottom: 16px;"
                        />
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <ui-button variant="primary" @click="executeQuery">Execute Query</ui-button>
                                <ui-text size="sm" class="text-muted">Use SELECT, INSERT, UPDATE, DELETE</ui-text>
                            </div>
                            <ui-button size="sm" @click="sqlQuery = ''">Clear</ui-button>
                        </div>
                        
                        <template v-if="queryResult">
                            <ui-divider style="margin: 24px 0;" />
                            <ui-title :level="4" style="margin-bottom: 12px;">Result:</ui-title>
                            <div v-if="queryResult.error" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 12px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.2);">
                                {{ queryResult.error }}
                            </div>
                            <div v-else>
                                <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                                    <ui-text weight="bold" style="color: #22c55e;">✓ {{ queryResult.type === 'select' ? 'Query executed' : 'Modification successful' }}</ui-text>
                                    <ui-text v-if="queryResult.type === 'select'" size="sm">Rows returned: {{ queryResult.count }}</ui-text>
                                    <ui-text v-if="queryResult.type === 'modification'" size="sm">Affected rows: {{ queryResult.affected_rows }}</ui-text>
                                </div>
                                
                                <ui-card v-if="queryResult.results && queryResult.results.length > 0" style="padding: 0;">
                                    <ui-data-table 
                                        :data="queryResult.results"
                                        :columns="Object.keys(queryResult.results[0]).map(key => ({ label: key, prop: key }))"
                                    />
                                </ui-card>
                            </div>
                        </template>
                    </ui-card>
                </ui-col>

                <!-- Table Browser -->
                <ui-col :span="24">
                    <ui-card>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <ui-title :level="3">Table Browser</ui-title>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <ui-text size="sm" weight="bold">Table:</ui-text>
                                <select v-model="selectedTable" @change="loadTableData" style="background: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 8px; cursor: pointer;">
                                    <option value="">-- Select a table --</option>
                                    <option v-for="table in tables" :key="table" :value="table">{{ table }}</option>
                                </select>
                                <ui-button size="sm" @click="loadTables">
                                    <LucideIcon name="refresh-cw" size="14" />
                                </ui-button>
                            </div>
                        </div>

                        <div v-if="tableData">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                <ui-title :level="4">{{ tableData.table }} <span style="font-weight: normal; opacity: 0.5;">({{ tableData.count }} rows)</span></ui-title>
                                <ui-button variant="primary" size="sm" @click="openCreateModal">
                                    <LucideIcon name="plus" size="14" style="margin-right: 8px;" /> Add Record
                                </ui-button>
                            </div>

                            <!-- Schema Info -->
                            <details style="margin-bottom: 24px; background: var(--bg-secondary); border-radius: 8px; overflow: hidden;">
                                <summary style="padding: 12px 16px; cursor: pointer; font-weight: bold; background: var(--bg-secondary);">Schema Information</summary>
                                <div style="padding: 0;">
                                    <ui-data-table 
                                        :data="tableData.schema"
                                        :columns="[
                                            { label: 'Column', prop: 'name' },
                                            { label: 'Type', prop: 'type' },
                                            { label: 'PK', render: (row) => row.pk ? '✓' : '' },
                                            { label: 'Not Null', render: (row) => row.notnull ? '✓' : '' },
                                            { label: 'Default', prop: 'dflt_value' }
                                        ]"
                                    />
                                </div>
                            </details>

                            <!-- Data Table -->
                            <ui-card style="padding: 0;">
                                <ui-data-table 
                                    :data="sortedData"
                                    :columns="[
                                        { 
                                            label: 'Actions', 
                                            width: '120px',
                                            render: (row) => h('div', { style: 'display: flex; gap: 8px;' }, [
                                                h(UIButton, { size: 'sm', title: 'Edit', onClick: () => openEditModal(row) }, () => h(Icon, { name: 'edit-2', size: 14 })),
                                                h(UIButton, { size: 'sm', variant: 'danger', title: 'Delete', onClick: () => deleteRecord(row.id) }, () => h(Icon, { name: 'trash', size: 14 }))
                                            ])
                                        },
                                        ...tableData.schema.map(col => ({
                                            label: col.name,
                                            prop: col.name,
                                            render: (row) => formatDisplayValue(row[col.name])
                                        }))
                                    ]"
                                />
                            </ui-card>
                        </div>
                        <div v-else style="padding: 60px; text-align: center; opacity: 0.3;">
                            <LucideIcon name="table" size="48" style="margin-bottom: 16px;" />
                            <ui-text style="display: block;">Select a table to browse its data</ui-text>
                        </div>
                    </ui-card>
                </ui-col>
            </ui-row>

            <!-- Edit/Create Modal -->
            <ui-modal v-model="showModal" :title="modalMode === 'create' ? 'Create New Record' : 'Edit Record #' + editingId" @close="closeModal">
                <div style="display: flex; flex-direction: column; gap: 20px; padding: 10px 0;">
                    <div v-for="col in formColumns" :key="col.name">
                        <ui-text weight="bold" size="sm" style="display: block; margin-bottom: 8px;">
                            {{ col.name }} 
                            <span style="opacity: 0.5; font-weight: normal; font-size: 0.8em; margin-left: 8px;">{{ col.type }}</span>
                            <span v-if="col.notnull" style="color: #ef4444; margin-left: 4px;">*</span>
                        </ui-text>
                        
                        <ui-textarea 
                            v-if="isLongText(col.type)"
                            v-model="formData[col.name]"
                            :rows="4"
                            :placeholder="col.dflt_value || '...'"
                        />
                        <ui-input 
                            v-else
                            :type="isNumber(col.type) ? 'number' : 'text'"
                            v-model="formData[col.name]"
                            :placeholder="col.dflt_value || '...'"
                        />
                    </div>
                </div>
                
                <template #footer>
                    <ui-button @click="closeModal">Cancel</ui-button>
                    <ui-button variant="primary" @click="saveRecord">Save {{ modalMode === 'create' ? 'Record' : 'Changes' }}</ui-button>
                </template>
            </ui-modal>
        </ui-container>
    `,
    setup() {
        const tables = ref([]);
        const selectedTable = ref('');
        const tableData = ref(null);
        const sqlQuery = ref('');
        const queryResult = ref(null);

        const rowsProxy = ref([]);
        watch(() => tableData.value, (val) => {
            rowsProxy.value = val ? [...val.data] : [];
        });

        const { sortColumn, sortDirection, sortBy, sortedData } = useSorting(rowsProxy);

        const showModal = ref(false);
        const modalMode = ref('create');
        const editingId = ref(null);
        const formData = ref({});

        const loadTables = async () => {
            try {
                const res = await fetch('/@/admin/db/tables');
                const data = await res.json();
                tables.value = data.tables;
            } catch (e) {
                console.error('Failed to load tables', e);
            }
        };

        const loadTableData = async () => {
            if (!selectedTable.value) {
                tableData.value = null;
                return;
            }
            try {
                const res = await fetch(`/@/admin/db/table/${selectedTable.value}`);
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.schema) {
                        tableData.value = data;
                        sortColumn.value = null;
                        sortDirection.value = 'asc';
                    } else {
                        throw new Error("Invalid table data received");
                    }
                } else {
                    tableData.value = null;
                    console.error('Failed to load table data: ' + res.status);
                }
            } catch (e) {
                console.error('Failed to load table data', e);
                tableData.value = null;
            }
        };

        const executeQuery = async () => {
            if (!sqlQuery.value.trim()) {
                store.addNotification('Please enter a SQL query', 'warning');
                return;
            }
            try {
                const res = await fetch('/@/admin/db/query', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ query: sqlQuery.value })
                });
                queryResult.value = await res.json();
                if (queryResult.value.error) {
                    store.addNotification('Query failed: ' + queryResult.value.error, 'error');
                } else {
                    store.addNotification('Query executed successfully', 'success');
                }
            } catch (e) {
                queryResult.value = { error: 'Failed to execute query: ' + e.message };
                store.addNotification('Query failed', 'error');
            }
        };

        const formColumns = computed(() => {
            if (!tableData.value) return [];
            return tableData.value.schema.filter(c => c.name !== 'id');
        });

        const isLongText = (type) => ['TEXT', 'CLOB'].some(t => type && type.toUpperCase().includes(t));
        const isNumber = (type) => ['INT', 'REAL', 'FLO', 'DOUB', 'NUM'].some(t => type && type.toUpperCase().includes(t));

        const openCreateModal = () => {
            modalMode.value = 'create';
            formData.value = {};
            showModal.value = true;
        };

        const openEditModal = (row) => {
            modalMode.value = 'edit';
            editingId.value = row.id;
            formData.value = { ...row };
            showModal.value = true;
        };

        const closeModal = () => {
            showModal.value = false;
            formData.value = {};
            editingId.value = null;
        };

        const saveRecord = async () => {
            try {
                const url = modalMode.value === 'create'
                    ? `/@/admin/db/table/${selectedTable.value}`
                    : `/@/admin/db/table/${selectedTable.value}/${editingId.value}`;

                const method = modalMode.value === 'create' ? 'POST' : 'PATCH';

                const payload = { ...formData.value };
                delete payload.id;

                const res = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    store.addNotification('Record saved successfully', 'success');
                    await loadTableData();
                    closeModal();
                } else {
                    const error = await res.json();
                    store.addNotification('Save failed: ' + (error.error || 'Unknown error'), 'error');
                }
            } catch (e) {
                store.addNotification('Failed to save record', 'error');
            }
        };

        const deleteRecord = async (id) => {
            const confirmed = await store.showConfirm({
                title: 'Delete Record',
                message: 'Are you sure you want to delete this record? This action cannot be undone.',
                confirmText: 'Delete',
                danger: true
            });

            if (!confirmed) return;

            try {
                const res = await fetch(`/@/admin/db/table/${selectedTable.value}/${id}`, {
                    method: 'DELETE'
                });
                if (res.ok) {
                    store.addNotification('Record deleted successfully', 'success');
                    await loadTableData();
                } else {
                    const error = await res.json();
                    store.addNotification('Delete failed: ' + (error.error || 'Unknown error'), 'error');
                }
            } catch (e) {
                store.addNotification('Failed to delete record', 'error');
            }
        };

        const formatDisplayValue = (val) => {
            if (val === null) return 'NULL';
            if (val === undefined) return '';
            if (typeof val === 'string' && val.length > 50) return val.substring(0, 50) + '...';
            return val;
        };

        onMounted(loadTables);

        return {
            tables,
            selectedTable,
            tableData,
            sortedData,
            sqlQuery,
            queryResult,
            showModal,
            modalMode,
            editingId,
            formData,
            formColumns,
            sortColumn,
            sortDirection,
            loadTables,
            loadTableData,
            sortBy,
            executeQuery,
            openCreateModal,
            openEditModal,
            closeModal,
            saveRecord,
            deleteRecord,
            isLongText,
            isNumber,
            formatDisplayValue
        };
    }
};

