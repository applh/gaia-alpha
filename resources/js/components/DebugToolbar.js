
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
                <button @click.stop="isMinimized = !isMinimized">{{ isMinimized ? '▲' : '▼' }}</button>
            </div>
        </div>
        <div class="debug-toolbar-body" v-if="!isMinimized">
            <div class="tabs">
                <button :class="{ active: currentTab === 'queries' }" @click="currentTab = 'queries'">SQL Queries ({{ data.queries.length }})</button>
                <button :class="{ active: currentTab === 'tasks' }" @click="currentTab = 'tasks'">Tasks ({{ data.tasks ? data.tasks.length : 0 }})</button>
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

                <div v-if="currentTab === 'queries'" class="tab-pane">
                    <table class="debug-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Query</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(query, index) in data.queries" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td class="code-cell">{{ query.sql }}</td>
                                <td :class="{ 'text-danger': query.duration > 0.05 }">{{ formatTime(query.duration) }}ms</td>
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
                }
                .text-danger {
                    color: #f87171;
                }
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
            `;
            document.head.appendChild(style);
        }

        const data = window.GAIA_DEBUG_DATA || {
            queries: [],
            tasks: [],
            time: { total: 0, start: 0 },
            memory: { peak: 0, current: 0, start: 0 },
            post: {},
            get: {},
            user: { username: 'Guest', level: 0 }
        };

        const isMinimized = Vue.ref(true);
        const currentTab = Vue.ref('queries');
        const visible = Vue.ref(!!window.GAIA_DEBUG_DATA);

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
                // It's [Object, 'method']
                // We can't easily serialize the object instance to JSON in PHP without recursion issues
                // So PHP probably sent it as [ {}, "method" ] or similar.
                // Actually Debug.php json_encode calls might fail on closures or objects.
                // But wait, Route::add stores the handler.
                return 'Controller Method';
            }
            return 'Closure/Function';
        };

        const toggleMinimize = () => {
            isMinimized.value = !isMinimized.value;
        };

        const logout = async () => {
            if (confirm('Logout?')) {
                try {
                    const res = await fetch('/@/logout', { method: 'POST' });
                    if (res.ok) {
                        window.location.reload();
                    }
                } catch (e) {
                    console.error(e);
                }
            }
        };

        return {
            data,
            isMinimized,
            currentTab,
            visible,
            formatTime,
            formatMemory,
            formatHandler,
            toggleMinimize,
            logout
        };
    }
};

// Mount it detached from the main app to avoid conflicts? 
// Or just register it as a global component?
// Since it relies on generic window data, we can just create a new app instance for it 
// and append it to the body.

if (window.GAIA_DEBUG_DATA) {
    const debugContainer = document.createElement('div');
    debugContainer.id = 'gaia-debug-toolbar-root';
    document.body.appendChild(debugContainer);

    // Check if we need to load Vue (it might handle page where Vue isn't loaded)
    // But currently all our pages load Vue via modules or CDN. 
    // Assuming Vue is available on window or we can import it.
    // The main app uses import map.

    // We'll trust that this script is module type and imports Vue.
}

export default DebugToolbar;
