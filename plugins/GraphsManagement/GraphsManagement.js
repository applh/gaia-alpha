import { ref, onMounted, computed } from 'vue';
import Icon from 'ui/Icon.js';
import ChartPreview from '/min/js/plugins/GraphsManagement/resources/js/components/ChartPreview.js';

export default {
    components: { LucideIcon: Icon, ChartPreview },
    template: `
    <div class="admin-page">
        <div class="admin-header">
            <h2 class="page-title">
                <LucideIcon name="bar-chart-2" size="32" />
                Graphs Management
            </h2>
            <div class="button-group">
                <button v-if="viewMode === 'list'" @click="viewMode = 'collections'" class="btn btn-secondary">
                    <LucideIcon name="layout-grid" size="16" />
                    Collections
                </button>
                <button v-if="viewMode === 'collections'" @click="viewMode = 'list'" class="btn btn-secondary">
                    <LucideIcon name="bar-chart-2" size="16" />
                    Graphs
                </button>
                <button v-if="viewMode === 'list'" @click="createNewGraph" class="btn btn-primary">
                    <LucideIcon name="plus" size="16" />
                    New Graph
                </button>
                <button v-if="viewMode === 'collections'" @click="createNewCollection" class="btn btn-primary">
                    <LucideIcon name="plus" size="16" />
                    New Collection
                </button>
            </div>
        </div>

        <!-- Graph List View -->
        <div v-if="viewMode === 'list'">
            <div class="admin-card" style="margin-bottom: 20px;">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Search graphs..." 
                        class="form-input"
                        style="flex: 1;"
                    />
                    <select v-model="filterChartType" class="form-select" style="width: 200px;">
                        <option value="">All Chart Types</option>
                        <option value="line">Line</option>
                        <option value="bar">Bar</option>
                        <option value="pie">Pie</option>
                        <option value="doughnut">Doughnut</option>
                        <option value="area">Area</option>
                        <option value="scatter">Scatter</option>
                        <option value="radar">Radar</option>
                        <option value="polarArea">Polar Area</option>
                    </select>
                    <button @click="loadGraphs" class="btn btn-secondary">
                        <LucideIcon name="refresh-cw" size="16" />
                        Refresh
                    </button>
                </div>
            </div>

            <div v-if="loading" class="admin-card" style="padding: 40px; text-align: center;">
                Loading graphs...
            </div>

            <div v-else-if="filteredGraphs.length === 0" class="admin-card" style="padding: 40px; text-align: center; color: var(--text-muted);">
                No graphs found. Create your first graph to get started!
            </div>

            <div v-else style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                <div v-for="graph in filteredGraphs" :key="graph.id" class="admin-card" style="cursor: pointer; transition: transform 0.2s;" @click="editGraph(graph)">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <div>
                            <h3 style="margin: 0 0 5px 0;">{{ graph.title }}</h3>
                            <p style="margin: 0; font-size: 0.875rem; color: var(--text-muted);">{{ graph.description || 'No description' }}</p>
                        </div>
                        <span class="badge" :style="{ background: getChartTypeColor(graph.chart_type) }">{{ graph.chart_type }}</span>
                    </div>
                    <div style="height: 200px; margin: 15px 0;">
                        <ChartPreview 
                            v-if="graphPreviews[graph.id]"
                            :chartType="graph.chart_type"
                            :data="graphPreviews[graph.id]"
                            :options="graph.chart_config || {}"
                        />
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid var(--border-color, #eee);">
                        <span style="font-size: 0.75rem; color: var(--text-muted);">
                            <LucideIcon :name="getDataSourceIcon(graph.data_source_type)" size="14" />
                            {{ graph.data_source_type }}
                        </span>
                        <div style="display: flex; gap: 5px;">
                            <button @click.stop="deleteGraph(graph.id)" class="btn btn-sm btn-danger">
                                <LucideIcon name="trash-2" size="14" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graph Editor View -->
        <div v-if="viewMode === 'edit'" class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>{{ currentGraph.id ? 'Edit Graph' : 'Create New Graph' }}</h3>
                <button @click="viewMode = 'list'" class="btn btn-secondary">
                    <LucideIcon name="x" size="16" />
                    Cancel
                </button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Left: Configuration -->
                <div>
                    <div class="form-group">
                        <label>Title *</label>
                        <input v-model="currentGraph.title" type="text" class="form-input" placeholder="My Graph" />
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea v-model="currentGraph.description" class="form-input" rows="2" placeholder="Optional description"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Chart Type *</label>
                        <select v-model="currentGraph.chart_type" class="form-select">
                            <option value="line">Line Chart</option>
                            <option value="bar">Bar Chart</option>
                            <option value="pie">Pie Chart</option>
                            <option value="doughnut">Doughnut Chart</option>
                            <option value="area">Area Chart</option>
                            <option value="scatter">Scatter Plot</option>
                            <option value="radar">Radar Chart</option>
                            <option value="polarArea">Polar Area Chart</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Data Source Type *</label>
                        <select v-model="currentGraph.data_source_type" class="form-select">
                            <option value="manual">Manual Data</option>
                            <option value="database">Database Query</option>
                            <option value="api">External API</option>
                        </select>
                    </div>

                    <!-- Manual Data Source -->
                    <div v-if="currentGraph.data_source_type === 'manual'" class="form-group">
                        <label>Chart Data (JSON) *</label>
                        <textarea 
                            v-model="manualDataJson" 
                            class="form-input" 
                            rows="8" 
                            placeholder='{"labels": ["A", "B", "C"], "datasets": [{"label": "Data", "data": [10, 20, 30]}]}'
                            style="font-family: monospace; font-size: 0.875rem;"
                        ></textarea>
                        <small style="color: var(--text-muted);">Enter Chart.js data format</small>
                    </div>

                    <!-- Database Query Source -->
                    <div v-if="currentGraph.data_source_type === 'database'">
                        <div class="form-group">
                            <label>SQL Query (SELECT only) *</label>
                            <textarea 
                                v-model="currentGraph.data_source_config.query" 
                                class="form-input" 
                                rows="4" 
                                placeholder="SELECT page_path, COUNT(*) as visits FROM cms_analytics_visits GROUP BY page_path"
                                style="font-family: monospace; font-size: 0.875rem;"
                            ></textarea>
                        </div>
                        <div class="form-group">
                            <label>Label Column</label>
                            <input v-model="currentGraph.data_source_config.label_column" type="text" class="form-input" placeholder="page_path" />
                        </div>
                        <div class="form-group">
                            <label>Value Column</label>
                            <input v-model="currentGraph.data_source_config.value_column" type="text" class="form-input" placeholder="visits" />
                        </div>
                        <div class="form-group">
                            <label>Dataset Label</label>
                            <input v-model="currentGraph.data_source_config.dataset_label" type="text" class="form-input" placeholder="Page Visits" />
                        </div>
                    </div>

                    <!-- API Source -->
                    <div v-if="currentGraph.data_source_type === 'api'">
                        <div class="form-group">
                            <label>API URL *</label>
                            <input v-model="currentGraph.data_source_config.url" type="text" class="form-input" placeholder="https://api.example.com/data" />
                        </div>
                        <div class="form-group">
                            <label>Data Path (optional)</label>
                            <input v-model="currentGraph.data_source_config.data_path" type="text" class="form-input" placeholder="data.results" />
                            <small style="color: var(--text-muted);">Dot notation path to data in response</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input v-model="currentGraph.is_public" type="checkbox" />
                            <span>Allow public embedding</span>
                        </label>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button @click="saveGraph" class="btn btn-primary">
                            <LucideIcon name="save" size="16" />
                            Save Graph
                        </button>
                        <button @click="testDataSource" class="btn btn-secondary">
                            <LucideIcon name="play" size="16" />
                            Test Data Source
                        </button>
                    </div>
                </div>

                <!-- Right: Preview -->
                <div>
                    <h4 style="margin-bottom: 15px;">Live Preview</h4>
                    <div class="admin-card" style="height: 400px; padding: 20px;">
                        <ChartPreview 
                            v-if="previewData"
                            :chartType="currentGraph.chart_type"
                            :data="previewData"
                            :options="currentGraph.chart_config || {}"
                        />
                        <div v-else style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-muted);">
                            Configure data source and test to see preview
                        </div>
                    </div>

                    <div v-if="currentGraph.id" style="margin-top: 20px;">
                        <h4 style="margin-bottom: 10px;">Embed Code</h4>
                        <div class="admin-card" style="padding: 15px;">
                            <code style="display: block; padding: 10px; background: var(--bg-secondary, #f5f5f5); border-radius: 4px; font-size: 0.875rem;">
                                [graph id="{{ currentGraph.id }}"]
                            </code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collections View -->
        <div v-if="viewMode === 'collections'">
            <div v-if="loading" class="admin-card" style="padding: 40px; text-align: center;">
                Loading collections...
            </div>

            <div v-else-if="collections.length === 0" class="admin-card" style="padding: 40px; text-align: center; color: var(--text-muted);">
                No collections found. Create a collection to group related graphs!
            </div>

            <div v-else style="display: grid; gap: 20px;">
                <div v-for="collection in collections" :key="collection.id" class="admin-card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h3 style="margin: 0 0 5px 0;">{{ collection.name }}</h3>
                            <p style="margin: 0; color: var(--text-muted);">{{ collection.description || 'No description' }}</p>
                            <p style="margin: 5px 0 0 0; font-size: 0.875rem; color: var(--text-muted);">
                                {{ collection.graph_ids.length }} graph(s)
                            </p>
                        </div>
                        <button @click="deleteCollection(collection.id)" class="btn btn-sm btn-danger">
                            <LucideIcon name="trash-2" size="14" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `,
    setup() {
        const graphs = ref([]);
        const collections = ref([]);
        const loading = ref(false);
        const viewMode = ref('list'); // list, edit, collections
        const currentGraph = ref({
            title: '',
            description: '',
            chart_type: 'line',
            data_source_type: 'manual',
            data_source_config: {},
            chart_config: {},
            is_public: false
        });
        const manualDataJson = ref('');
        const previewData = ref(null);
        const graphPreviews = ref({});
        const searchQuery = ref('');
        const filterChartType = ref('');

        const filteredGraphs = computed(() => {
            let result = graphs.value;

            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                result = result.filter(g =>
                    g.title.toLowerCase().includes(query) ||
                    (g.description && g.description.toLowerCase().includes(query))
                );
            }

            if (filterChartType.value) {
                result = result.filter(g => g.chart_type === filterChartType.value);
            }

            return result;
        });

        const loadGraphs = async () => {
            loading.value = true;
            try {
                const res = await fetch('/@/graphs');
                if (res.ok) {
                    graphs.value = await res.json();
                    // Load preview data for each graph
                    for (const graph of graphs.value) {
                        loadGraphPreview(graph.id);
                    }
                }
            } catch (err) {
                console.error('Failed to load graphs:', err);
            } finally {
                loading.value = false;
            }
        };

        const loadGraphPreview = async (graphId) => {
            try {
                const res = await fetch(`/@/graphs/${graphId}/data`);
                if (res.ok) {
                    const { data } = await res.json();
                    graphPreviews.value[graphId] = data;
                }
            } catch (err) {
                console.error(`Failed to load preview for graph ${graphId}:`, err);
            }
        };

        const loadCollections = async () => {
            loading.value = true;
            try {
                const res = await fetch('/@/graphs/collections');
                if (res.ok) {
                    collections.value = await res.json();
                }
            } catch (err) {
                console.error('Failed to load collections:', err);
            } finally {
                loading.value = false;
            }
        };

        const createNewGraph = () => {
            currentGraph.value = {
                title: '',
                description: '',
                chart_type: 'line',
                data_source_type: 'manual',
                data_source_config: {},
                chart_config: {},
                is_public: false
            };
            manualDataJson.value = JSON.stringify({
                labels: ['January', 'February', 'March', 'April', 'May'],
                datasets: [{
                    label: 'Sample Data',
                    data: [12, 19, 3, 5, 2]
                }]
            }, null, 2);
            previewData.value = null;
            viewMode.value = 'edit';
        };

        const editGraph = (graph) => {
            currentGraph.value = { ...graph };
            if (graph.data_source_type === 'manual') {
                manualDataJson.value = JSON.stringify(graph.data_source_config, null, 2);
            }
            previewData.value = graphPreviews.value[graph.id] || null;
            viewMode.value = 'edit';
        };

        const saveGraph = async () => {
            try {
                // Prepare data source config
                let dataSourceConfig = currentGraph.value.data_source_config;
                if (currentGraph.value.data_source_type === 'manual') {
                    try {
                        dataSourceConfig = JSON.parse(manualDataJson.value);
                    } catch (err) {
                        alert('Invalid JSON in manual data');
                        return;
                    }
                }

                const payload = {
                    title: currentGraph.value.title,
                    description: currentGraph.value.description,
                    chart_type: currentGraph.value.chart_type,
                    data_source_type: currentGraph.value.data_source_type,
                    data_source_config: dataSourceConfig,
                    chart_config: currentGraph.value.chart_config,
                    is_public: currentGraph.value.is_public ? 1 : 0
                };

                let res;
                if (currentGraph.value.id) {
                    res = await fetch(`/@/graphs/${currentGraph.value.id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                } else {
                    res = await fetch('/@/graphs', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                }

                if (res.ok) {
                    alert('Graph saved successfully!');
                    viewMode.value = 'list';
                    loadGraphs();
                } else {
                    const error = await res.json();
                    alert('Failed to save graph: ' + (error.error || 'Unknown error'));
                }
            } catch (err) {
                console.error('Save error:', err);
                alert('Failed to save graph');
            }
        };

        const deleteGraph = async (id) => {
            if (!confirm('Are you sure you want to delete this graph?')) return;

            try {
                const res = await fetch(`/@/graphs/${id}`, { method: 'DELETE' });
                if (res.ok) {
                    loadGraphs();
                }
            } catch (err) {
                console.error('Delete error:', err);
            }
        };

        const testDataSource = async () => {
            try {
                let dataSourceConfig = currentGraph.value.data_source_config;
                if (currentGraph.value.data_source_type === 'manual') {
                    try {
                        dataSourceConfig = JSON.parse(manualDataJson.value);
                        previewData.value = dataSourceConfig;
                        return;
                    } catch (err) {
                        alert('Invalid JSON in manual data');
                        return;
                    }
                }

                // For database and API, we need to save first to test
                if (!currentGraph.value.id) {
                    alert('Please save the graph first to test database or API data sources');
                    return;
                }

                const res = await fetch(`/@/graphs/${currentGraph.value.id}/data`);
                if (res.ok) {
                    const { data } = await res.json();
                    previewData.value = data;
                } else {
                    const error = await res.json();
                    alert('Failed to fetch data: ' + (error.error || 'Unknown error'));
                }
            } catch (err) {
                console.error('Test error:', err);
                alert('Failed to test data source');
            }
        };

        const createNewCollection = () => {
            const name = prompt('Collection name:');
            if (!name) return;

            const description = prompt('Description (optional):');

            fetch('/@/graphs/collections', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name,
                    description,
                    graph_ids: []
                })
            }).then(res => {
                if (res.ok) {
                    loadCollections();
                }
            });
        };

        const deleteCollection = async (id) => {
            if (!confirm('Are you sure you want to delete this collection?')) return;

            try {
                const res = await fetch(`/@/graphs/collections/${id}`, { method: 'DELETE' });
                if (res.ok) {
                    loadCollections();
                }
            } catch (err) {
                console.error('Delete error:', err);
            }
        };

        const getChartTypeColor = (type) => {
            const colors = {
                line: '#3b82f6',
                bar: '#8b5cf6',
                pie: '#ec4899',
                doughnut: '#f59e0b',
                area: '#10b981',
                scatter: '#06b6d4',
                radar: '#6366f1',
                polarArea: '#14b8a6'
            };
            return colors[type] || '#6b7280';
        };

        const getDataSourceIcon = (type) => {
            const icons = {
                manual: 'edit',
                database: 'database',
                api: 'globe'
            };
            return icons[type] || 'file';
        };

        onMounted(() => {
            loadGraphs();
            loadCollections();
        });

        return {
            graphs,
            collections,
            loading,
            viewMode,
            currentGraph,
            manualDataJson,
            previewData,
            graphPreviews,
            searchQuery,
            filterChartType,
            filteredGraphs,
            loadGraphs,
            createNewGraph,
            editGraph,
            saveGraph,
            deleteGraph,
            testDataSource,
            createNewCollection,
            deleteCollection,
            getChartTypeColor,
            getDataSourceIcon
        };
    }
};
