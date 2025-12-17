
const DebugToolbar = {
    template: `
    <div v-if="visible" class="debug-toolbar" :class="{ minimized: isMinimized }">
        <div class="debug-toolbar-header" @click="toggleMinimize">
            <span class="brand">Gaia Debug</span>
            <div class="metrics">
                <span class="metric">
                    <span class="label">Time:</span>
                    <span class="value">{{ formatTime(data.time.total) }}ms</span>
                </span>
                <span class="metric">
                    <span class="label">Memory:</span>
                    <span class="value">{{ formatMemory(data.memory.peak) }}</span>
                </span>
                <span class="metric">
                    <span class="label">Queries:</span>
                    <span class="value">{{ data.queries.length }}</span>
                </span>
                <span class="metric" v-if="data.user">
                    <span class="label">User:</span>
                    <span class="value">{{ data.user.username }} ({{ data.user.level }})</span>
                </span>
            </div>
            <div class="controls" style="display:flex; align-items:center;">
                <button @click.stop="logout" title="Logout" style="margin-right:10px;">⏻</button>
                <button @click.stop="toggleMinimize">{{ isMinimized ? '▲' : '▼' }}</button>
            </div>
        </div>
        <div class="debug-toolbar-body" v-if="!isMinimized">
            <div class="tabs">
                <button :class="{ active: currentTab === 'queries' }" @click="currentTab = 'queries'">SQL Queries ({{ data.queries.length }})</button>
                <button :class="{ active: currentTab === 'tasks' }" @click="currentTab = 'tasks'">Tasks ({{ data.tasks ? data.tasks.length : 0 }})</button>
                <button :class="{ active: currentTab === 'plugins' }" @click="currentTab = 'plugins'">Plugins ({{ data.plugin_logs ? data.plugin_logs.length : 0 }})</button>
                <button :class="{ active: currentTab === 'network' }" @click="currentTab = 'network'">Network ({{ requests.length }})</button>
                <button :class="{ active: currentTab === 'request' }" @click="currentTab = 'request'">Request</button>
                <button :class="{ active: currentTab === 'globals' }" @click="currentTab = 'globals'">Globals</button>
            </div>
            <div class="tab-content">
                <div v-if="currentTab === 'tasks'" class="tab-pane">
                    <table class="debug-table">
                        <thead>
                            <tr>
                                <th>Step</th>
                                <th>Task</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(task, index) in data.tasks" :key="index">
                                <td>{{ task.step }}</td>
                                <td class="code-cell">{{ task.task }}</td>
                                <td :class="{ 'text-danger': task.duration > 0.05 }">{{ formatTime(task.duration) }}ms</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="currentTab === 'plugins'" class="tab-pane">
                    <table class="debug-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Plugin</th>
                                <th>Message</th>
                                <th>Context</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!data.plugin_logs || data.plugin_logs.length === 0">
                                <td colspan="4" style="text-align:center; color:#666;">No plugin logs recorded.</td>
                            </tr>
                            <tr v-else v-for="(log, index) in data.plugin_logs" :key="index">
                                <td>{{ formatTime(log.time) }}ms</td>
                                <td><strong>{{ log.plugin }}</strong></td>
                                <td class="code-cell">{{ log.message }}</td>
                                <td>
                                    <pre v-if="log.context && Object.keys(log.context).length" style="font-size:0.85em; margin:0; max-height:100px; overflow:auto;">{{ JSON.stringify(log.context, null, 2) }}</pre>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="currentTab === 'queries'" class="tab-pane">
                    <table class="debug-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Source</th>
                                <th>Query</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(query, index) in data.queries" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td>
                                    <span v-if="query.source" class="badge-source">{{ query.source }}</span>
                                    <span v-else class="badge-source main">Main</span>
                                </td>
                                <td class="code-cell">{{ query.sql }}</td>
                                <td :class="{ 'text-danger': query.duration > 0.05 }">{{ formatTime(query.duration) }}ms</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="currentTab === 'network'" class="tab-pane">
                     <table class="debug-table">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th>SQL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(req, index) in requests" :key="index">
                                <td>{{ req.method }}</td>
                                <td class="code-cell">{{ req.url }}</td>
                                <td :class="getStatusClass(req.status)">{{ req.status }}</td>
                                <td>{{ formatTime(req.duration/1000) }}ms</td>
                                <td>{{ req.sqlCount }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="currentTab === 'request'" class="tab-pane">
                    <h3>Route</h3>
                    <div v-if="data.route">
                        <p><strong>Method:</strong> {{ data.route.route.method }}</p>
                        <p><strong>Path:</strong> {{ data.route.route.path }}</p>
                        <p><strong>Handler:</strong> {{ formatHandler(data.route.route.handler) }}</p>
                        <p><strong>Params:</strong> {{ JSON.stringify(data.route.params) }}</p>
                    </div>
                    <div v-else>
                        <p>No route matched (or data missing)</p>
                    </div>

                    <h3>Timing</h3>
                    <p>Start: {{ new Date(data.time.start * 1000).toISOString() }}</p>
                    <p>Total Duration: {{ formatTime(data.time.total) }}ms</p>
                    
                    <h3>Memory</h3>
                    <p>Start: {{ formatMemory(data.memory.start) }}</p>
                    <p>Current: {{ formatMemory(data.memory.current) }}</p>
                    <p>Peak: {{ formatMemory(data.memory.peak) }}</p>
                </div>
                 <div v-if="currentTab === 'globals'" class="tab-pane">
                    <h3>$_POST</h3>
                    <pre>{{ JSON.stringify(data.post, null, 2) }}</pre>
                    <h3>$_GET</h3>
                    <pre>{{ JSON.stringify(data.get, null, 2) }}</pre>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="debug-toggle" @click="visible = true">🐞</div>
    `,
    setup() {
        // Inject Styles
        if (!document.getElementById('gaia-debug-styles')) {
            const style = document.createElement('style');
            style.id = 'gaia-debug-styles';
            style.textContent = `
                .debug-toolbar {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    width: 100%;
                    background: #18181b;
                    color: #fff;
                    font-family: monospace;
                    border-top: 1px solid #333;
                    z-index: 10000;
                    box-shadow: 0 -2px 10px rgba(0,0,0,0.5);
                }
                .debug-toolbar.minimized {
                    width: auto;
                    left: auto;
                    right: 20px;
                    bottom: 0;
                    border-radius: 8px 8px 0 0;
                }
                .debug-toolbar-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 5px 10px;
                    background: #27272a;
                    cursor: pointer;
                    height: 36px;
                }
                .debug-toolbar-header .brand {
                    font-weight: bold;
                    color: #a78bfa;
                    margin-right: 20px;
                }
                .debug-toolbar-header .metrics {
                    display: flex;
                    gap: 15px;
                }
                .metric .label {
                    color: #71717a;
                    margin-right: 4px;
                }
                .metric .value {
                    color: #e4e4e7;
                }
                .debug-toolbar-body {
                    height: 300px;
                    overflow: auto;
                    background: #09090b;
                }
                .tabs {
                    display: flex;
                    border-bottom: 1px solid #333;
                    background: #18181b;
                }
                .tabs button {
                    background: none;
                    border: none;
                    color: #a1a1aa;
                    padding: 8px 16px;
                    cursor: pointer;
                    font-family: inherit;
                    border-right: 1px solid #333;
                }
                .tabs button.active {
                    background: #27272a;
                    color: #fff;
                }
                .tab-content {
                    padding: 0;
                }
                .tab-pane {
                    padding: 10px;
                }
                .debug-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 12px;
                }
                .debug-table th, .debug-table td {
                    text-align: left;
                    padding: 4px 8px;
                    border-bottom: 1px solid #27272a;
                }
                .debug-table th {
                    color: #71717a;
                }
                .debug-table .code-cell {
                    font-family: monospace;
                    color: #a78bfa;
                    word-break: break-all;
                }
                .text-danger {
                    color: #f87171;
                }
                .text-success { color: #4ade80; }
                .text-warning { color: #facc15; }
                
                .debug-toggle {
                    position: fixed;
                    bottom: 10px;
                    right: 10px;
                    background: #27272a;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    z-index: 10000;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
                    font-size: 20px;
                }
                .controls button {
                    background: none;
                    border: none;
                    color: #fff;
                    cursor: pointer;
                    padding: 4px;
                }
                .badge-source {
                    padding: 2px 4px;
                    border-radius: 4px;
                    font-size: 10px;
                    background: #3f3f46;
                    color: #eee;
                }
                .badge-source.main {
                    background: #1e1b4b;
                    color: #818cf8;
                }
            `;
            document.head.appendChild(style);
        }

        // Safe access to global data, with fallback if replacement failed
        let initialData = window.GAIA_DEBUG_DATA;

        const defaultData = {
            queries: [],
            tasks: [],
            plugin_logs: [],
            route: {},
            memory: { current: 0, peak: 0, start: 0 },
            time: { total: 0, start: 0 },
            post: {},
            get: {},
            user: { username: 'Guest', level: 0 }
        };

        // If placeholder wasn't replaced (e.g. non-admin or error), use default
        if (!initialData || typeof initialData !== 'object' || initialData === '__GAIA_DEBUG_DATA_PLACEHOLDER__') {
            initialData = defaultData;
        }

        const data = Vue.reactive(initialData);
        const requests = Vue.reactive([]);
        const visible = Vue.ref(true);
        const isMinimized = Vue.ref(localStorage.getItem('gaia_debug_minimized') === 'true');
        const currentTab = Vue.ref(localStorage.getItem('gaia_debug_tab') || 'queries');

        Vue.watch(currentTab, (newTab) => {
            localStorage.setItem('gaia_debug_tab', newTab);
        });

        // Intercept Fetch
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const start = performance.now();
            const method = args[1]?.method || 'GET';
            const url = typeof args[0] === 'string' ? args[0] : args[0].url;

            const reqRecord = Vue.reactive({
                method,
                url,
                status: '...',
                duration: 0,
                sqlCount: 0
            });
            requests.push(reqRecord);

            try {
                const response = await originalFetch(...args);
                const end = performance.now();
                reqRecord.duration = end - start;
                reqRecord.status = response.status;

                const debugHeader = response.headers.get('X-Gaia-Debug');
                if (debugHeader) {
                    try {
                        const serverDebug = JSON.parse(debugHeader);
                        if (serverDebug.queries) {
                            reqRecord.sqlCount = serverDebug.queries.length;
                            // Merge queries
                            serverDebug.queries.forEach(q => {
                                data.queries.push({
                                    ...q,
                                    source: `${method} ${url}`
                                });
                            });
                        }

                        // Merge tasks
                        if (serverDebug.tasks) {
                            serverDebug.tasks.forEach(t => {
                                data.tasks.push({
                                    ...t,
                                    step: `AJAX: ${t.step}`, // Prefix to distinguish
                                });
                            });
                        }

                        // Merge plugin logs
                        if (serverDebug.plugin_logs) {
                            serverDebug.plugin_logs.forEach(l => {
                                data.plugin_logs.push({
                                    ...l,
                                    plugin: `AJAX: ${l.plugin}`
                                });
                            });
                        }

                    } catch (e) {
                        console.error('Failed to parse debug header', e);
                    }
                }

                return response;
            } catch (error) {
                reqRecord.status = 'Error';
                throw error;
            }
        };

        const formatTime = (seconds) => {
            return (seconds * 1000).toFixed(2);
        };

        const formatMemory = (bytes) => {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        };

        const formatHandler = (handler) => {
            if (!handler) return 'Unknown';
            if (typeof handler === 'string') return handler;
            if (Array.isArray(handler)) {
                return 'Controller Method';
            }
            return 'Closure/Function';
        };

        const getStatusClass = (status) => {
            if (status >= 200 && status < 300) return 'text-success';
            if (status >= 400) return 'text-danger';
            return 'text-warning';
        };

        const toggleMinimize = () => {
            isMinimized.value = !isMinimized.value;
            localStorage.setItem('gaia_debug_minimized', isMinimized.value);
        };

        const logout = async () => {
            try {
                const res = await fetch('/@/logout', { method: 'POST' });
                if (res.ok) {
                    window.location.reload();
                }
            } catch (e) {
                console.error(e);
            }
        };

        return {
            data,
            requests,
            isMinimized,
            currentTab,
            visible,
            formatTime,
            formatMemory,
            formatHandler,
            getStatusClass,
            toggleMinimize,
            logout
        };
    }
};

// Mount handled by injection script in ViewController
export default DebugToolbar;
