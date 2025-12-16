import { ref, onMounted, computed, watch } from 'vue';
import { useSorting } from 'composables/useSorting.js';
import Icon from 'ui/Icon.js';
import Modal from 'ui/Modal.js';

export default {
    components: { Modal },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">Database Manager</h2>
            </div>
            
            <!-- SQL Query Executor -->
            <div class="admin-card sql-executor">
                <h3>SQL Query Executor</h3>
                <textarea 
                    v-model="sqlQuery" 
                    placeholder="Enter SQL query here..."
                    rows="6"
                    class="sql-input"
                ></textarea>
                <div class="executor-actions" style="display: flex; align-items: center; gap: 15px;">
                    <button @click="executeQuery" class="btn-primary">Execute Query</button>
                    <span class="hint">Use SELECT, INSERT, UPDATE, DELETE</span>
                </div>
                
                <div v-if="queryResult" class="query-result">
                    <h4>Result:</h4>
                    <div v-if="queryResult.error" class="error">{{ queryResult.error }}</div>
                    <div v-else>
                        <p class="success">✓ {{ queryResult.type === 'select' ? 'Query executed' : 'Modification successful' }}</p>
                        <p v-if="queryResult.type === 'select'">Rows returned: {{ queryResult.count }}</p>
                        <p v-if="queryResult.type === 'modification'">Affected rows: {{ queryResult.affected_rows }}</p>
                        
                        <div v-if="queryResult.results && queryResult.results.length > 0" class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th v-for="key in Object.keys(queryResult.results[0])" :key="key">{{ key }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, idx) in queryResult.results" :key="idx">
                                        <td v-for="key in Object.keys(row)" :key="key">{{ row[key] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Browser -->
            <div class="admin-card table-browser">
                <h3>Table Browser</h3>
                
                <div class="table-selector">
                    <label>Select Table:</label>
                    <select v-model="selectedTable" @change="loadTableData">
                        <option value="">-- Select a table --</option>
                        <option v-for="table in tables" :key="table" :value="table">{{ table }}</option>
                    </select>
                    <button @click="loadTables" class="btn-secondary">Refresh Tables</button>
                </div>

                <div v-if="tableData" class="table-data">
                    <div class="table-header">
                        <h4>{{ tableData.table }} ({{ tableData.count }} rows)</h4>
                        <button @click="openCreateModal" class="btn-primary">Add New Record</button>
                    </div>

                    <!-- Schema Info -->
                    <details class="schema-details">
                        <summary>Schema Information</summary>
                        <table>
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Type</th>
                                    <th>PK</th>
                                    <th>NotNull</th>
                                    <th>Default</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="col in tableData.schema" :key="col.cid">
                                    <td>{{ col.name }}</td>
                                    <td>{{ col.type }}</td>
                                    <td>{{ col.pk ? '✓' : '' }}</td>
                                    <td>{{ col.notnull ? '✓' : '' }}</td>
                                    <td>{{ col.dflt_value || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </details>

                    <!-- Data Table -->
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th class="actions-header">Actions</th>
                                    <th 
                                        v-for="col in tableData.schema" 
                                        :key="col.name"
                                        @click="sortBy(col.name)"
                                        class="sortable-header"
                                    >
                                        {{ col.name }}
                                        <span v-if="sortColumn === col.name" class="sort-indicator">
                                            {{ sortDirection === 'asc' ? '▲' : '▼' }}
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in sortedData" :key="row.id">
                                    <td class="actions">
                                        <button @click="openEditModal(row)" class="btn-small" title="Edit Record">Edit</button>
                                        <button @click="deleteRecord(row.id)" class="btn-small btn-danger" title="Delete Record">Delete</button>
                                    </td>
                                    <td v-for="col in tableData.schema" :key="col.name">
                                        {{ formatDisplayValue(row[col.name]) }}
                                    </td>
                                </tr>
                                <tr v-if="sortedData.length === 0">
                                    <td :colspan="(tableData.schema ? tableData.schema.length : 0) + 1" class="text-center">No records found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Edit/Create Modal -->
            <Modal :show="showModal" :title="modalMode === 'create' ? 'Create New Record' : 'Edit Record #' + editingId" @close="closeModal">
                <form @submit.prevent="saveRecord">
                    <div v-for="col in formColumns" :key="col.name" class="form-group">
                        <label>
                            {{ col.name }} 
                            <span class="type-hint">{{ col.type }}</span>
                            <span v-if="col.notnull" class="required">*</span>
                        </label>
                        
                        <!-- Textarea for text/clob -->
                        <textarea 
                            v-if="isLongText(col.type)"
                            v-model="formData[col.name]"
                            rows="4"
                            :placeholder="col.dflt_value"
                        ></textarea>
                        
                        <!-- Number input -->
                        <input 
                            v-else-if="isNumber(col.type)"
                            type="number"
                            v-model="formData[col.name]"
                            :placeholder="col.dflt_value"
                        >
                        
                        <!-- Standard input -->
                        <input 
                            v-else
                            type="text"
                            v-model="formData[col.name]"
                            :placeholder="col.dflt_value"
                        >
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Save {{ modalMode === 'create' ? 'Record' : 'Changes' }}</button>
                        <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </Modal>
        </div>
    `,
    setup() {
        const tables = ref([]);
        const selectedTable = ref('');
        const tableData = ref(null);
        const sqlQuery = ref('');
        const queryResult = ref(null);

        // Use Composables
        // Note: rawData is a computed prop that references tableData, so we need to bridge it slightly
        const rawRows = computed(() => tableData.value ? tableData.value.data : []);
        // We can't use useSorting directly on a Ref<Array> easily if the array is replaced entirely.
        // Actually useSorting takes a Ref. So we can pass a computed or a ref that we update.
        // But tableData.value.data is what we want to sort.
        // Let's create a proxy ref
        const rowsProxy = ref([]);

        watch(() => tableData.value, (val) => {
            rowsProxy.value = val ? [...val.data] : [];
        });

        const { sortColumn, sortDirection, sortBy, sortedData } = useSorting(rowsProxy);

        // Modal State
        const showModal = ref(false);
        const modalMode = ref('create'); // 'create' or 'edit'
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
                        // Reset Sort
                        sortColumn.value = null;
                        sortDirection.value = 'asc';
                    } else {
                        throw new Error("Invalid table data received");
                    }
                } else {
                    tableData.value = null; // Clear on error
                    console.error('Failed to load table data: ' + res.status);
                }
            } catch (e) {
                console.error('Failed to load table data', e);
                tableData.value = null;
            }
        };

        const executeQuery = async () => {
            if (!sqlQuery.value.trim()) {
                alert('Please enter a SQL query');
                return;
            }
            try {
                const res = await fetch('/@/admin/db/query', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ query: sqlQuery.value })
                });
                queryResult.value = await res.json();
            } catch (e) {
                queryResult.value = { error: 'Failed to execute query: ' + e.message };
            }
        };

        // Modal Logic
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
            formData.value = { ...row }; // Clone row data
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

                // Prepare payload: remove ID to avoid updating PK
                const payload = { ...formData.value };
                delete payload.id;

                const res = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    await loadTableData();
                    closeModal();
                } else {
                    const error = await res.json();
                    alert('Save failed: ' + (error.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Failed to save record: ' + e.message);
            }
        };

        const deleteRecord = async (id) => {
            try {
                const res = await fetch(`/@/admin/db/table/${selectedTable.value}/${id}`, {
                    method: 'DELETE'
                });
                if (res.ok) {
                    await loadTableData();
                } else {
                    const error = await res.json();
                    alert('Delete failed: ' + (error.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Failed to delete record: ' + e.message);
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
