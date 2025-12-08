import { ref, onMounted } from 'vue';

export default {
    template: `
        <div class="database-manager">
            <h2>Database Manager</h2>
            
            <!-- SQL Query Executor -->
            <div class="sql-executor card">
                <h3>SQL Query Executor</h3>
                <textarea 
                    v-model="sqlQuery" 
                    placeholder="Enter SQL query here..."
                    rows="6"
                    class="sql-input"
                ></textarea>
                <button @click="executeQuery" class="btn-primary">Execute Query</button>
                
                <div v-if="queryResult" class="query-result">
                    <h4>Result:</h4>
                    <div v-if="queryResult.error" class="error">{{ queryResult.error }}</div>
                    <div v-else>
                        <p class="success">✓ {{ queryResult.type === 'select' ? 'Query executed' : 'Query executed' }}</p>
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
            <div class="table-browser card">
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
                        <button @click="showCreateForm = !showCreateForm" class="btn-primary">
                            {{ showCreateForm ? 'Cancel' : 'Add New Record' }}
                        </button>
                    </div>

                    <!-- Create Form -->
                    <div v-if="showCreateForm" class="record-form card">
                        <h4>Create New Record</h4>
                        <div v-for="col in tableData.schema.filter(c => c.name !== 'id')" :key="col.name" class="form-group">
                            <label>{{ col.name }} ({{ col.type }})</label>
                            <input 
                                v-model="newRecord[col.name]" 
                                :type="getInputType(col.type)"
                                :placeholder="col.name"
                            />
                        </div>
                        <button @click="createRecord" class="btn-primary">Create</button>
                    </div>

                    <!-- Schema Info -->
                    <details class="schema-details">
                        <summary>Schema Information</summary>
                        <table>
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Type</th>
                                    <th>Not Null</th>
                                    <th>Default</th>
                                    <th>Primary Key</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="col in tableData.schema" :key="col.cid">
                                    <td>{{ col.name }}</td>
                                    <td>{{ col.type }}</td>
                                    <td>{{ col.notnull ? 'Yes' : 'No' }}</td>
                                    <td>{{ col.dflt_value || '-' }}</td>
                                    <td>{{ col.pk ? 'Yes' : 'No' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </details>

                    <!-- Data Table -->
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th v-for="col in tableData.schema" :key="col.name">{{ col.name }}</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in tableData.data" :key="row.id">
                                    <td v-for="col in tableData.schema" :key="col.name">
                                        <span v-if="editingRow !== row.id">{{ row[col.name] }}</span>
                                        <input 
                                            v-else-if="col.name !== 'id'"
                                            v-model="editData[col.name]"
                                            :type="getInputType(col.type)"
                                        />
                                        <span v-else>{{ row[col.name] }}</span>
                                    </td>
                                    <td class="actions">
                                        <template v-if="editingRow !== row.id">
                                            <button @click="startEdit(row)" class="btn-small">Edit</button>
                                            <button @click="deleteRecord(row.id)" class="btn-small btn-danger">Delete</button>
                                        </template>
                                        <template v-else>
                                            <button @click="saveEdit(row.id)" class="btn-small btn-success">Save</button>
                                            <button @click="cancelEdit" class="btn-small">Cancel</button>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup() {
        const tables = ref([]);
        const selectedTable = ref('');
        const tableData = ref(null);
        const sqlQuery = ref('');
        const queryResult = ref(null);
        const editingRow = ref(null);
        const editData = ref({});
        const showCreateForm = ref(false);
        const newRecord = ref({});

        const loadTables = async () => {
            try {
                const res = await fetch('/api/admin/db/tables');
                const data = await res.json();
                tables.value = data.tables;
            } catch (e) {
                console.error('Failed to load tables', e);
            }
        };

        const loadTableData = async () => {
            if (!selectedTable.value) return;

            try {
                const res = await fetch(`/api/admin/db/table/${selectedTable.value}`);
                const data = await res.json();
                tableData.value = data;
                showCreateForm.value = false;
                newRecord.value = {};
            } catch (e) {
                console.error('Failed to load table data', e);
            }
        };

        const executeQuery = async () => {
            if (!sqlQuery.value.trim()) {
                alert('Please enter a SQL query');
                return;
            }

            try {
                const res = await fetch('/api/admin/db/query', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ query: sqlQuery.value })
                });
                queryResult.value = await res.json();
            } catch (e) {
                queryResult.value = { error: 'Failed to execute query: ' + e.message };
            }
        };

        const startEdit = (row) => {
            editingRow.value = row.id;
            editData.value = { ...row };
        };

        const cancelEdit = () => {
            editingRow.value = null;
            editData.value = {};
        };

        const saveEdit = async (id) => {
            try {
                const dataToUpdate = { ...editData.value };
                delete dataToUpdate.id; // Don't update the ID

                const res = await fetch(`/api/admin/db/table/${selectedTable.value}/${id}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataToUpdate)
                });

                if (res.ok) {
                    await loadTableData();
                    cancelEdit();
                } else {
                    const error = await res.json();
                    alert('Update failed: ' + (error.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Failed to update record: ' + e.message);
            }
        };

        const deleteRecord = async (id) => {
            if (!confirm('Are you sure you want to delete this record?')) return;

            try {
                const res = await fetch(`/api/admin/db/table/${selectedTable.value}/${id}`, {
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

        const createRecord = async () => {
            try {
                const res = await fetch(`/api/admin/db/table/${selectedTable.value}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(newRecord.value)
                });

                if (res.ok) {
                    await loadTableData();
                    newRecord.value = {};
                    showCreateForm.value = false;
                } else {
                    const error = await res.json();
                    alert('Create failed: ' + (error.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Failed to create record: ' + e.message);
            }
        };

        const getInputType = (sqlType) => {
            if (sqlType.includes('INT')) return 'number';
            if (sqlType.includes('DATE') || sqlType.includes('TIME')) return 'datetime-local';
            return 'text';
        };

        onMounted(loadTables);

        return {
            tables,
            selectedTable,
            tableData,
            sqlQuery,
            queryResult,
            editingRow,
            editData,
            showCreateForm,
            newRecord,
            loadTables,
            loadTableData,
            executeQuery,
            startEdit,
            cancelEdit,
            saveEdit,
            deleteRecord,
            createRecord,
            getInputType
        };
    }
};
