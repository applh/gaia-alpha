const DatabaseConnections = {
    template: `
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-6">Database Connections</h1>
            
            <div class="grid grid-cols-12 gap-6">
                <!-- Sidebar List -->
                <div class="col-span-12 md:col-span-4 lg:col-span-3">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-4 border-b flex justify-between items-center">
                            <h2 class="font-semibold text-gray-700">Connections</h2>
                            <button @click="createNew" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                                + New
                            </button>
                        </div>
                        <div class="divide-y max-h-[600px] overflow-y-auto">
                            <div v-for="conn in connections" :key="conn.id" 
                                 @click="selectConnection(conn)"
                                 class="p-4 cursor-pointer hover:bg-gray-50"
                                 :class="{'bg-blue-50 border-l-4 border-blue-600': selected && selected.id === conn.id}">
                                <div class="font-medium text-gray-900">{{ conn.name }}</div>
                                <div class="text-xs text-gray-500 mt-1 flex justify-between">
                                    <span class="uppercase badge badge-sm">{{ conn.type }}</span>
                                    <span>{{ conn.host || 'local' }}</span>
                                </div>
                            </div>
                            <div v-if="connections.length === 0" class="p-8 text-center text-gray-500 text-sm">
                                No connections found.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-span-12 md:col-span-8 lg:col-span-9">
                    <!-- Edit Form -->
                    <div v-if="mode === 'edit' || mode === 'create'" class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold mb-4">{{ mode === 'create' ? 'New Connection' : 'Edit Connection' }}</h2>
                        
                        <form @submit.prevent="saveConnection" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Connection Name</label>
                                <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required placeholder="e.g. Prod MySQL">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Type</label>
                                    <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="mysql">MySQL / MariaDB</option>
                                        <option value="pgsql">PostgreSQL</option>
                                        <option value="sqlite">SQLite</option>
                                    </select>
                                </div>
                                <div v-if="form.type !== 'sqlite'">
                                    <label class="block text-sm font-medium text-gray-700">Port</label>
                                    <input v-model="form.port" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" :placeholder="defaultPort">
                                </div>
                            </div>

                            <div v-if="form.type !== 'sqlite'" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Host</label>
                                    <input v-model="form.host" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="localhost">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Database Name</label>
                                    <input v-model="form.database" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                </div>
                            </div>
                            
                            <div v-else>
                                <label class="block text-sm font-medium text-gray-700">Database Path</label>
                                <input v-model="form.database" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required placeholder="/absolute/path/to/db.sqlite">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Username</label>
                                    <input v-model="form.username" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Password</label>
                                    <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Leave empty to keep current">
                                </div>
                            </div>

                            <div class="flex justify-between items-center pt-4 border-t">
                                <button type="button" @click="testConnection" class="text-gray-600 hover:text-gray-900 font-medium">
                                    <i class="fas fa-plug mr-1"></i> Test Connection
                                </button>
                                <div class="flex space-x-3">
                                    <button type="button" @click="cancelEdit" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">Save Connection</button>
                                </div>
                            </div>
                            
                            <div v-if="testResult" :class="testResult.success ? 'text-green-600' : 'text-red-600'" class="mt-2 text-sm font-medium">
                                {{ testResult.message }}
                            </div>
                        </form>
                    </div>

                    <!-- Query Interface -->
                    <div v-else-if="selected" class="bg-white rounded-lg shadow flex flex-col h-[600px]">
                        <div class="p-4 border-b flex justify-between items-center bg-gray-50">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">{{ selected.name }}</h2>
                                <p class="text-xs text-gray-500">{{ selected.username }}@{{ selected.host }} / {{ selected.database }}</p>
                            </div>
                            <div class="flex space-x-2">
                                <button @click="editCurrent" class="text-gray-600 hover:text-blue-600 p-2"><i class="fas fa-edit"></i></button>
                                <button @click="deleteCurrent" class="text-gray-600 hover:text-red-600 p-2"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>

                        <div class="p-4 flex-none border-b">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">SQL Query</label>
                            <div class="relative">
                                <textarea v-model="query" class="w-full h-32 font-mono text-sm p-3 border rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="SELECT * FROM users LIMIT 10;"></textarea>
                                <button @click="runQuery" class="absolute bottom-3 right-3 bg-green-600 text-white px-4 py-1 rounded shadow hover:bg-green-700 text-sm">
                                    <i class="fas fa-play mr-1"></i> Run
                                </button>
                            </div>
                        </div>

                        <div class="flex-grow overflow-auto p-4 bg-gray-50">
                            <div v-if="queryLoading" class="flex justify-center items-center h-full text-gray-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Executing...
                            </div>
                            <div v-else-if="queryError" class="bg-red-50 text-red-700 p-4 rounded border border-red-200">
                                <strong>Error:</strong> {{ queryError }}
                            </div>
                            <div v-else-if="queryResult">
                                <div v-if="queryResult.type === 'write'" class="bg-green-50 text-green-700 p-4 rounded border border-green-200">
                                    Success! {{ queryResult.affected }} rows affected.
                                </div>
                                <div v-else-if="queryResult.data && queryResult.data.length > 0">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 border rounded bg-white">
                                            <thead>
                                                <tr>
                                                    <th v-for="key in Object.keys(queryResult.data[0])" :key="key" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100">
                                                        {{ key }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <tr v-for="(row, idx) in queryResult.data" :key="idx" class="hover:bg-gray-50">
                                                    <td v-for="val in Object.values(row)" class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">
                                                        {{ val }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500 text-right">
                                        {{ queryResult.data.length }} rows returned.
                                    </div>
                                </div>
                                <div v-else class="text-center text-gray-500 py-8">
                                    No results found.
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
                                <i class="fas fa-terminal text-4xl mb-2"></i>
                                <p>Enter a query to see results</p>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="flex flex-col items-center justify-center h-[400px] text-gray-400">
                        <i class="fas fa-database text-6xl mb-4"></i>
                        <p class="text-lg">Select a connection to manage</p>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup() {
        const { ref, computed, onMounted } = Vue;
        const connections = ref([]);
        const selected = ref(null);
        const mode = ref('view'); // view, create, edit

        const form = ref({
            id: null,
            name: '',
            type: 'mysql',
            host: 'localhost',
            port: 3306,
            database: '',
            username: '',
            password: ''
        });

        const query = ref('');
        const queryResult = ref(null);
        const queryError = ref(null);
        const queryLoading = ref(false);
        const testResult = ref(null);

        const defaultPort = computed(() => {
            return form.value.type === 'pgsql' ? 5432 : 3306;
        });

        const loadConnections = async () => {
            const res = await fetch('/@/admin/db-connections');
            const data = await res.json();
            connections.value = data.connections;
        };

        const createNew = () => {
            selected.value = null;
            form.value = {
                id: null,
                name: '',
                type: 'mysql',
                host: 'localhost',
                port: 3306,
                database: '',
                username: '',
                password: ''
            };
            mode.value = 'create';
            testResult.value = null;
        };

        const selectConnection = (conn) => {
            selected.value = conn;
            mode.value = 'view';
            query.value = '';
            queryResult.value = null;
            queryError.value = null;
        };

        const editCurrent = () => {
            form.value = { ...selected.value, password: '' }; // Don't show password
            mode.value = 'edit';
            testResult.value = null;
        };

        const cancelEdit = () => {
            mode.value = selected.value ? 'view' : 'view'; // If canceling creation without selection, go to empty view
        };

        const saveConnection = async () => {
            try {
                const res = await fetch('/@/admin/db-connections', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(form.value)
                });
                const data = await res.json();

                if (data.success) {
                    await loadConnections();
                    // Select the saved one from list
                    const found = connections.value.find(c => c.id == data.id);
                    if (found) selectConnection(found);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Save failed: ' + e.message);
            }
        };

        const deleteCurrent = async () => {
            if (!confirm(`Are you sure you want to delete ${selected.value.name}?`)) return;

            try {
                await fetch(`/@/admin/db-connections/${selected.value.id}`, { method: 'DELETE' });
                await loadConnections();
                selected.value = null;
                mode.value = 'view';
            } catch (e) {
                alert('Delete failed: ' + e.message);
            }
        };

        const testConnection = async () => {
            testResult.value = { success: false, message: 'Testing...' };
            try {
                const res = await fetch('/@/admin/db-connections/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(form.value)
                });
                const data = await res.json();

                if (data.success) {
                    testResult.value = { success: true, message: 'Connection successful!' };
                } else {
                    testResult.value = { success: false, message: 'Failed: ' + data.error };
                }
            } catch (e) {
                testResult.value = { success: false, message: 'Error: ' + e.message };
            }
        };

        const runQuery = async () => {
            if (!query.value) return;
            queryLoading.value = true;
            queryError.value = null;
            queryResult.value = null;

            try {
                const res = await fetch(`/@/admin/db-connections/${selected.value.id}/query`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ query: query.value })
                });
                const data = await res.json();

                if (data.success) {
                    queryResult.value = data.result;
                } else {
                    queryError.value = data.error;
                }
            } catch (e) {
                queryError.value = e.message;
            } finally {
                queryLoading.value = false;
            }
        };

        onMounted(() => {
            loadConnections();
        });

        return {
            connections,
            selected,
            mode,
            form,
            query,
            queryResult,
            queryError,
            queryLoading,
            testResult,
            defaultPort,
            createNew,
            selectConnection,
            editCurrent,
            cancelEdit,
            saveConnection,
            deleteCurrent,
            testConnection,
            runQuery
        };
    }
};

window.DatabaseConnections = DatabaseConnections;
