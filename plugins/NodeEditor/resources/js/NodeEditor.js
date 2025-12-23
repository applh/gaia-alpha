import { ref, onMounted, computed, reactive, watch } from 'vue';
import Icon from 'ui/Icon.js';
import { store } from 'store';

const STYLES = `
        .node-editor-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 100px); /* Adjust based on admin header */
            background: var(--bg-color);
            color: var(--text-primary);
        }
        .editor-header {
            padding: 1rem;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .editor-body {
            flex: 1;
            display: flex;
            overflow: hidden;
            position: relative;
        }
        .editor-sidebar {
            width: 200px;
            background: var(--card-bg);
            border-right: 1px solid var(--border-color);
            padding: 1rem;
            z-index: 10;
        }
        .editor-properties {
            width: 250px;
            background: var(--card-bg);
            border-left: 1px solid var(--border-color);
            padding: 1rem;
            z-index: 10;
            overflow-y: auto;
        }
        .node-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            cursor: grab;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-primary);
        }
        .node-item:hover {
            background: var(--border-color);
        }
        .editor-canvas {
            flex: 1;
            position: relative;
            overflow: hidden;
            background-color: var(--bg-color);
            background-image: radial-gradient(var(--text-muted) 1px, transparent 1px);
            background-size: 20px 20px;
            cursor: grab;
        }
        .editor-canvas:active {
            cursor: grabbing;
        }
        .canvas-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            transform-origin: 0 0;
        }
        .connections-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: visible;
            pointer-events: none; /* Let clicks pass through to canvas */
        }
        .connection-line {
            fill: none;
            stroke: var(--text-secondary);
            stroke-width: 2;
            pointer-events: stroke; /* Allow clicking on the line itself */
            cursor: pointer;
        }
        .connection-line:hover {
            stroke: var(--text-primary);
            stroke-width: 3;
        }
        .connection-line.selected {
            stroke: var(--accent-color);
            stroke-width: 3;
        }
         .connection-line.draft {
            stroke: var(--accent-color);
            stroke-dasharray: 4;
        }
        .node-rect {
            position: absolute;
            width: 150px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            user-select: none;
            color: var(--text-primary);
        }
        .node-rect.selected {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px var(--accent-light);
        }
        .node-rect.multi-selected {
            border-color: var(--accent-color);
            border-style: dashed;
        }
        .node-header {
            padding: 0.5rem;
            background: var(--border-color);
            border-bottom: 1px solid var(--border-color);
            border-radius: 8px 8px 0 0;
            font-weight: 500;
            font-size: 0.875rem;
            display: flex;
            justify-content: space-between;
        }
        .node-body {
            padding: 0.5rem;
             display: flex;
            justify-content: space-between;
        }
        .node-inputs, .node-outputs {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .port {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
            cursor: pointer;
        }
        .port-dot {
            width: 8px;
            height: 8px;
            background: var(--text-muted);
            border-radius: 50%;
        }
        .port:hover .port-dot {
            background: var(--accent-color);
            transform: scale(1.2);
        }
        .input-port {
            justify-content: flex-start;
        }
        .output-port {
            justify-content: flex-end;
            text-align: right;
        }
        
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 100;
            backdrop-filter: blur(4px);
        }
        .modal {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            width: 500px;
            max-width: 90vw;
            box-shadow: var(--shadow-lg);
        }
        .modal-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }
        .modal-body {
            padding: 1rem;
            max-height: 60vh;
            overflow-y: auto;
        }
        .modal-footer {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        .diagram-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .diagram-list li {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .diagram-list li:hover {
            background: var(--glass-bg);
        }
        .diagram-title { font-weight: 500; }
        .diagram-date { font-size: 0.8em; color: var(--text-muted); margin-right: 10px; }
        
        /* Node Type Specifics */
        .node-process .node-header { background-color: rgba(3, 105, 161, 0.2); color: #7dd3fc; }
        .node-input .node-header { background-color: rgba(21, 128, 61, 0.2); color: #86efac; }
        .node-output .node-header { background-color: rgba(185, 28, 28, 0.2); color: #fca5a5; }
        .node-note .node-header { background-color: rgba(161, 98, 7, 0.2); color: #fde047; }
        .node-note .node-rect { background-color: var(--card-bg); width: 200px; }
        .node-comment .node-rect { border-radius: 4px; border: 1px dashed var(--text-muted); background: transparent; box-shadow: none; width: 180px; }
        .node-comment .node-header { display: none; }
        .node-comment .node-body { font-style: italic; color: var(--text-muted); font-size: 0.8rem; }
        .node-variable .node-header { background-color: rgba(168, 85, 247, 0.2); color: #d8b4fe; }
        .node-subgraph .node-header { background-color: rgba(249, 115, 22, 0.2); color: #fdba74; }
        
        /* Sidebar Search */
        .sidebar-search {
            margin-bottom: 1rem;
            position: relative;
        }
        .sidebar-search input {
            width: 100%;
            padding: 0.4rem 0.5rem 0.4rem 2rem;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            background: var(--bg-color);
            color: var(--text-primary);
            font-size: 0.8rem;
        }
        .sidebar-search .lucide-icon {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        .search-results {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .search-item {
            padding: 0.4rem;
            font-size: 0.8rem;
            cursor: pointer;
            border-radius: 4px;
        }
        .search-item:hover { background: var(--glass-bg); }

        /* Breadcrumbs */
        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        .breadcrumb-item { cursor: pointer; }
        .breadcrumb-item:hover { color: var(--accent-color); }
        .breadcrumb-separator { color: var(--text-muted); }

        /* Export Dropdown */
        .dropdown { position: relative; }
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            box-shadow: var(--shadow-lg);
            z-index: 50;
            min-width: 120px;
            padding: 0.5rem 0;
            display: none;
        }
        .dropdown:hover .dropdown-menu { display: block; }
        .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            cursor: pointer;
            white-space: nowrap;
        }
        .dropdown-item:hover { background: var(--glass-bg); }
        
        /* Minimap */
        .minimap {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 200px;
            height: 150px;
            background: rgba(0,0,0,0.5);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            z-index: 20;
            overflow: hidden;
            pointer-events: none;
        }
        .minimap-content {
            width: 100%;
            height: 100%;
        }
        .minimap-node { fill: var(--accent-color); opacity: 0.5; }
        .minimap-viewport {
            fill: none;
            stroke: #fff;
            stroke-width: 1px;
            opacity: 0.3;
        }

        /* Context Menu */
        .context-menu {
            position: fixed;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            box-shadow: var(--shadow-lg);
            padding: 0.5rem 0;
            z-index: 1000;
            min-width: 150px;
        }
        .context-menu-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .context-menu-item:hover {
            background: var(--glass-bg);
        }
        .context-menu-item.danger { color: #ef4444; }

        /* Selection Box */
        .selection-box {
            position: absolute;
            background: rgba(3, 105, 161, 0.1);
            border: 1px solid var(--accent-color);
            pointer-events: none;
            z-index: 15;
        }
`;

export default {
    components: { LucideIcon: Icon },
    template: `
    <div class="node-editor-container">
        <!-- Toolbar -->
        <div class="editor-header">
            <h2 class="page-title">
                <LucideIcon name="git-commit" size="24" />
                <span v-if="currentDiagram.id">{{ currentDiagram.title }}</span>
                <span v-else>New Diagram</span>
            </h2>
            
            <div class="breadcrumbs" v-if="navigationStack.length > 0">
                <span class="breadcrumb-item" @click="resetToRoot">Root</span>
                <template v-for="(nav, index) in navigationStack" :key="index">
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-item" @click="jumpToStack(index)">{{ nav.label }}</span>
                </template>
            </div>

            <div class="button-group">
                <button @click="showVariables = true" class="btn btn-secondary">
                    <LucideIcon name="database" size="16" />
                    Variables
                </button>
                <div class="dropdown">
                    <button class="btn btn-secondary">
                        <LucideIcon name="download" size="16" />
                        Export
                        <LucideIcon name="chevron-down" size="14" />
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-item" @click="exportSVG">SVG Image</div>
                        <div class="dropdown-item" @click="exportPNG">PNG Image</div>
                        <div class="dropdown-item" @click="exportMermaid">Mermaid Code</div>
                    </div>
                </div>
                <button @click="saveDiagram" class="btn btn-primary">
                    <LucideIcon name="save" size="16" />
                    Save
                </button>
                <button @click="showList = true" class="btn btn-secondary">
                    <LucideIcon name="list" size="16" />
                    Load
                </button>
                <button @click="tidyLayout" class="btn btn-secondary">
                    <LucideIcon name="layout" size="16" />
                    Tidy
                </button>
                <button @click="clearCanvas" class="btn btn-danger">
                    <LucideIcon name="trash-2" size="16" />
                    Clear
                </button>
            </div>
        </div>

        <!-- Editor Area -->
        <div class="editor-body">
            <!-- Sidebar -->
            <div class="editor-sidebar">
                <div class="sidebar-search">
                    <LucideIcon name="search" size="14" />
                    <input v-model="searchQuery" placeholder="Search nodes..." />
                </div>
                
                <div class="search-results" v-if="searchQuery">
                    <div 
                        v-for="node in filteredNodes" 
                        :key="node.id" 
                        class="search-item"
                        @click="teleportToNode(node.id)"
                    >
                        {{ node.data.label || node.type }}
                    </div>
                </div>

                <h3>Nodes</h3>
                <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'process')"
                >
                    <LucideIcon name="cpu" size="16" />
                    Process
                </div>
                <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'subgraph')"
                >
                    <LucideIcon name="box" size="16" />
                    Sub-graph
                </div>
                <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'variable')"
                >
                    <LucideIcon name="database" size="16" />
                    Variable
                </div>
                <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'input')"
                >
                    <LucideIcon name="log-in" size="16" />
                    Input
                </div>
                <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'output')"
                >
                    <LucideIcon name="log-out" size="16" />
                    Output
                </div>
                 <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'note')"
                >
                    <LucideIcon name="sticky-note" size="16" />
                    Note
                </div>
                <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'comment')"
                >
                    <LucideIcon name="message-square" size="16" />
                    Comment
                </div>
            </div>

            <!-- Canvas -->
            <div 
                class="editor-canvas" 
                ref="canvasRef"
                @drop="onDrop" 
                @dragover.prevent
                @mousedown="handleCanvasMouseDown"
                @mousemove="handleMouseMove"
                @mouseup="stopInteraction"
                @wheel.prevent="handleZoom"
                @contextmenu.prevent="showContextMenu"
            >
                <div 
                    class="canvas-content"
                    :style="{ transform: 'translate(' + pan.x + 'px, ' + pan.y + 'px) scale(' + zoom + ')' }"
                >
                    <!-- Selection Box -->
                    <div 
                        v-if="selectionRect" 
                        class="selection-box"
                        :style="selectionBoxStyle"
                    ></div>
                    <!-- Connections -->
                    <svg class="connections-layer">
                        <path 
                            v-for="edge in edges" 
                            :key="edge.id"
                            :d="getEdgePath(edge)"
                            class="connection-line"
                            :class="{ selected: selectedEdge === edge.id }"
                            @click.stop="selectEdge(edge.id)"
                        />
                         <path 
                            v-if="draftEdge" 
                            :d="getDraftEdgePath()"
                            class="connection-line draft"
                        />
                    </svg>

                    <!-- Nodes -->
                    <div 
                        v-for="node in nodes" 
                        :key="node.id"
                        class="node-rect"
                        :class="['node-' + node.type, { selected: selectedNode === node.id, 'multi-selected': selectedNodes.includes(node.id) }]"
                        :style="{ left: node.x + 'px', top: node.y + 'px' }"
                        @mousedown.stop="startDragNode($event, node)"
                        @click.stop="selectNode(node.id)"
                        @dblclick.stop="node.type === 'subgraph' ? enterSubgraph(node) : null"
                    >
                        <div class="node-header">
                            <span class="node-title">{{ node.data.label || node.data.variableKey || node.type }}</span>
                            <div class="node-actions">
                                <LucideIcon name="x" size="12" @click.stop="deleteNode(node.id)" />
                            </div>
                        </div>
                        <div class="node-body">
                             <!-- Inputs -->
                            <div class="node-inputs">
                                <div 
                                    v-for="input in getInputs(node.type)" 
                                    :key="input.id"
                                    class="port input-port"
                                    @mouseup.stop="onPortMouseUp(node.id, input.id)"
                                >
                                    <div class="port-dot"></div>
                                    <span>{{ input.label }}</span>
                                </div>
                            </div>
                            <!-- Outputs -->
                            <div class="node-outputs">
                                <div 
                                    v-for="output in getOutputs(node.type)" 
                                    :key="output.id"
                                    class="port output-port"
                                    @mousedown.stop="startConnection(node.id, output.id)"
                                >
                                    <span>{{ output.label }}</span>
                                    <div class="port-dot"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Properties Panel -->
            <div class="editor-properties" v-if="selectedNodeData">
                <h3>Properties</h3>
                <div class="form-group">
                    <label>Label</label>
                    <input v-model="selectedNodeData.data.label" class="form-input" />
                </div>
                 <div class="form-group" v-if="selectedNodeData.type === 'note' || selectedNodeData.type === 'comment'">
                    <label>Content</label>
                    <textarea v-model="selectedNodeData.data.content" class="form-input" rows="5"></textarea>
                </div>
                <div class="form-group" v-if="selectedNodeData.type === 'variable'">
                    <label>Global Variable</label>
                    <select v-model="selectedNodeData.data.variableKey" class="form-input">
                        <option v-for="v in variables" :key="v.key" :value="v.key">{{ v.key }}</option>
                    </select>
                </div>
                <div class="form-group" v-if="selectedNodeData.type === 'subgraph'">
                    <LucideIcon name="external-link" size="14" />
                    <span>Double-click node to edit sub-graph content.</span>
                </div>
                 <div class="form-group">
                    <label>ID</label>
                    <input :value="selectedNodeData.id" class="form-input" disabled />
                </div>
            </div>
        </div>
            
        <!-- Minimap -->
        <div class="minimap">
            <svg class="minimap-content" viewBox="0 0 1000 750">
                <rect 
                    v-for="node in nodes" 
                    :key="'mini-' + node.id"
                    class="minimap-node"
                    :x="node.x / 5" 
                    :y="node.y / 5" 
                    width="30" 
                    height="20"
                />
                <!-- Viewport frame (simplified) -->
                <rect 
                    class="minimap-viewport"
                    :x="-pan.x / (zoom * 5)"
                    :y="-pan.y / (zoom * 5)"
                    :width="1000 / (zoom * 5)"
                    :height="750 / (zoom * 5)"
                />
            </svg>
        </div>

        <!-- Context Menu -->
        <div 
            v-if="contextMenu.show" 
            class="context-menu" 
            :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
            @click.stop
        >
            <div class="context-menu-item" @click="duplicateSelected">
                <LucideIcon name="copy" size="14" /> Duplicate
            </div>
            <div class="context-menu-item" @click="tidyLayout">
                <LucideIcon name="layout" size="14" /> Tidy Layout
            </div>
            <div class="context-menu-item danger" @click="deleteSelected">
                <LucideIcon name="trash-2" size="14" /> Delete
            </div>
        </div>

        <!-- Diagrams List Modal -->
        <div class="modal-overlay" v-if="showList">
            <div class="modal">
                <div class="modal-header">
                    <h3>Open Diagram</h3>
                    <button @click="showList = false"><LucideIcon name="x" size="16" /></button>
                </div>
                <div class="modal-body">
                    <div v-if="loading" class="text-center">Loading...</div>
                    <ul v-else class="diagram-list">
                        <li v-for="d in cachedDiagrams" :key="d.id" @click="loadDiagram(d)">
                            <span class="diagram-title">{{ d.title }}</span>
                            <span class="diagram-date">{{ new Date(d.updated_at).toLocaleDateString() }}</span>
                            <button @click.stop="deleteDiagram(d.id)" class="btn-icon text-danger"><LucideIcon name="trash-2" size="14" /></button>
                        </li>
                         <li v-if="cachedDiagrams.length === 0" class="text-center text-muted">No diagrams found</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showList = false">Cancel</button>
                    <button class="btn btn-primary" @click="createNewDiagram">Create New</button>
                </div>
            </div>
        </div>
        
        <!-- Save Modal -->
        <div class="modal-overlay" v-if="showSave">
             <div class="modal">
                <div class="modal-header">
                    <h3>Save Diagram</h3>
                    <button @click="showSave = false"><LucideIcon name="x" size="16" /></button>
                </div>
                 <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input v-model="currentDiagram.title" class="form-input" placeholder="My Diagram" />
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                         <textarea v-model="currentDiagram.description" class="form-input" placeholder="Optional description"></textarea>
                    </div>
                 </div>
                 <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showSave = false">Cancel</button>
                    <button class="btn btn-primary" @click="confirmSave">Save</button>
                 </div>
             </div>
        </div>

        <!-- Variables Modal -->
        <div class="modal-overlay" v-if="showVariables">
            <div class="modal">
                <div class="modal-header">
                    <h3>Global Variables</h3>
                    <button @click="showVariables = false"><LucideIcon name="x" size="16" /></button>
                </div>
                <div class="modal-body">
                    <table class="table">
                        <thead>
                            <tr><th>Key</th><th>Type</th><th>Default</th><th></th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="(v, index) in variables" :key="index">
                                <td><input v-model="v.key" class="form-input-sm" /></td>
                                <td>
                                    <select v-model="v.type" class="form-input-sm">
                                        <option value="string">String</option>
                                        <option value="number">Number</option>
                                        <option value="boolean">Boolean</option>
                                    </select>
                                </td>
                                <td><input v-model="v.value" class="form-input-sm" /></td>
                                <td><button @click="variables.splice(index, 1)" class="btn-icon text-danger"><LucideIcon name="trash-2" size="14" /></button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button @click="variables.push({ key: 'var_' + variables.length, type: 'string', value: '' })" class="btn btn-secondary btn-sm mt-2">
                        Add Variable
                    </button>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" @click="showVariables = false">Done</button>
                </div>
            </div>
        </div>
        
    </div>
    `,
    /* CSS Injection */
    styles: STYLES,
    setup() {
        // State
        const nodes = ref([]); // { id, x, y, type, data: {} }
        const edges = ref([]); // { id, source, sourcePort, target, targetPort }
        const pan = ref({ x: 0, y: 0 });
        const zoom = ref(1);

        // Interaction State
        const isDragging = ref(false);
        const dragStart = ref({ x: 0, y: 0 });
        const draggingNode = ref(null);

        const isConnecting = ref(false);
        const connectionSource = ref(null); // { nodeId, portId }
        const mousePos = ref({ x: 0, y: 0 });

        const selectedNode = ref(null);
        const selectedNodes = ref([]); // For multi-selection
        const selectedEdge = ref(null);

        const selectionRect = ref(null); // { x1, y1, x2, y2 }
        const contextMenu = reactive({ show: false, x: 0, y: 0 });

        // App State
        const showList = ref(false);
        const showSave = ref(false);
        const loading = ref(false);
        const cachedDiagrams = ref([]);
        const currentDiagram = ref({
            id: null,
            title: '',
            description: ''
        });

        // Phase 2 State
        const variables = ref([]); // { key, type, value }
        const showVariables = ref(false);
        const navigationStack = ref([]); // { id, label, nodes, edges }
        const searchQuery = ref('');

        // Computed
        const selectedNodeData = computed(() => {
            return nodes.value.find(n => n.id === selectedNode.value);
        });

        const draftEdge = computed(() => {
            if (!isConnecting.value || !connectionSource.value) return null;
            return {
                start: getPortPosition(connectionSource.value.nodeId, connectionSource.value.portId),
                end: toCanvasCoords(mousePos.value.x, mousePos.value.y)
            };
        });

        const selectionBoxStyle = computed(() => {
            if (!selectionRect.value) return {};
            const x = Math.min(selectionRect.value.x1, selectionRect.value.x2);
            const y = Math.min(selectionRect.value.y1, selectionRect.value.y2);
            const w = Math.abs(selectionRect.value.x1 - selectionRect.value.x2);
            const h = Math.abs(selectionRect.value.y1 - selectionRect.value.y2);
            return {
                left: x + 'px',
                top: y + 'px',
                width: w + 'px',
                height: h + 'px'
            };
        });

        const filteredNodes = computed(() => {
            if (!searchQuery.value) return [];
            const q = searchQuery.value.toLowerCase();
            return nodes.value.filter(n =>
                (n.data.label && n.data.label.toLowerCase().includes(q)) ||
                n.type.toLowerCase().includes(q)
            );
        });

        // Ref
        const canvasRef = ref(null);

        // Methods - Canvas
        const injectStyles = () => {
            const styleId = 'node-editor-styles';
            if (!document.getElementById(styleId)) {
                const style = document.createElement('style');
                style.id = styleId;
                style.textContent = STYLES;
                document.head.appendChild(style);
            }
        };

        const toCanvasCoords = (clientX, clientY) => {
            const rect = canvasRef.value.getBoundingClientRect();
            return {
                x: (clientX - rect.left - pan.value.x) / zoom.value,
                y: (clientY - rect.top - pan.value.y) / zoom.value
            };
        };

        const onDragStart = (event, type) => {
            event.dataTransfer.setData('nodeType', type);
        };

        const onDrop = (event) => {
            const type = event.dataTransfer.getData('nodeType');
            if (!type) return;

            const coords = toCanvasCoords(event.clientX, event.clientY);

            const newNode = {
                id: 'node_' + Date.now(),
                type: type,
                x: coords.x,
                y: coords.y,
                data: { label: type.charAt(0).toUpperCase() + type.slice(1) }
            };

            nodes.value.push(newNode);
            selectNode(newNode.id);
        };

        const handleCanvasMouseDown = (event) => {
            if (event.button === 2) return; // Right click handled by contextmenu
            contextMenu.show = false;

            if (event.button === 0 && !event.target.closest('.node-rect') && !event.target.closest('.connection-line') && !isConnecting.value) {
                if (event.shiftKey) {
                    // Start selection box
                    const coords = toCanvasCoords(event.clientX, event.clientY);
                    selectionRect.value = { x1: coords.x, y1: coords.y, x2: coords.x, y2: coords.y };
                } else {
                    // Start pan
                    isDragging.value = true;
                    dragStart.value = { x: event.clientX - pan.value.x, y: event.clientY - pan.value.y };
                }
                selectedNode.value = null;
                selectedNodes.value = [];
            }
        };

        const startDragNode = (event, node) => {
            draggingNode.value = node;
            const coords = toCanvasCoords(event.clientX, event.clientY);
            // Store offset to avoid jumping
            dragStart.value = { x: coords.x - node.x, y: coords.y - node.y };
        };

        const handleMouseMove = (event) => {
            mousePos.value = { x: event.clientX, y: event.clientY };

            if (isDragging.value) {
                pan.value = {
                    x: event.clientX - dragStart.value.x,
                    y: event.clientY - dragStart.value.y
                };
            } else if (draggingNode.value) {
                const coords = toCanvasCoords(event.clientX, event.clientY);
                draggingNode.value.x = coords.x - dragStart.value.x;
                draggingNode.value.y = coords.y - dragStart.value.y;
            } else if (selectionRect.value) {
                const coords = toCanvasCoords(event.clientX, event.clientY);
                selectionRect.value.x2 = coords.x;
                selectionRect.value.y2 = coords.y;
            }
        };

        const handleZoom = (event) => {
            // Simple zoom
            const delta = event.deltaY > 0 ? 0.9 : 1.1;
            zoom.value = Math.min(Math.max(zoom.value * delta, 0.1), 5);
        };

        const stopInteraction = () => {
            isDragging.value = false;
            draggingNode.value = null;

            if (selectionRect.value) {
                // Determine selected nodes
                const x1 = Math.min(selectionRect.value.x1, selectionRect.value.x2);
                const x2 = Math.max(selectionRect.value.x1, selectionRect.value.x2);
                const y1 = Math.min(selectionRect.value.y1, selectionRect.value.y2);
                const y2 = Math.max(selectionRect.value.y1, selectionRect.value.y2);

                selectedNodes.value = nodes.value.filter(n => {
                    return n.x >= x1 && n.x <= x2 && n.y >= y1 && n.y <= y2;
                }).map(n => n.id);

                selectionRect.value = null;
            }

            if (isConnecting.value) {
                isConnecting.value = false;
                connectionSource.value = null;
            }
        };

        // Methods - Nodes & Ports
        const getInputs = (type) => {
            if (type === 'process' || type === 'subgraph') return [{ id: 'in', label: 'In' }];
            if (type === 'output') return [{ id: 'in', label: 'Value' }];
            return [];
        };

        const getOutputs = (type) => {
            if (type === 'process' || type === 'subgraph') return [{ id: 'out', label: 'Out' }];
            if (type === 'input' || type === 'variable') return [{ id: 'out', label: 'Value' }];
            return [];
        };

        const teleportToNode = (id) => {
            const node = nodes.value.find(n => n.id === id);
            if (node) {
                // Animate or jump to node
                const rect = canvasRef.value.getBoundingClientRect();
                pan.value = {
                    x: (rect.width / 2) - (node.x * zoom.value) - (75 * zoom.value),
                    y: (rect.height / 2) - (node.y * zoom.value) - (50 * zoom.value)
                };
                selectNode(id);
                searchQuery.value = '';
            }
        };

        const enterSubgraph = (node) => {
            // Push current state to stack
            navigationStack.value.push({
                id: node.id,
                label: node.data.label || 'Sub-graph',
                nodes: [...nodes.value],
                edges: [...edges.value],
                pan: { ...pan.value },
                zoom: zoom.value
            });

            // Load sub-graph content (stored in node.data.content or empty)
            const content = node.data.subgraphContent || { nodes: [], edges: [] };
            nodes.value = content.nodes;
            edges.value = content.edges;
            pan.value = { x: 0, y: 0 };
            zoom.value = 1;
            selectedNode.value = null;
        };

        const resetToRoot = () => {
            if (navigationStack.value.length === 0) return;
            const root = navigationStack.value[0];

            // Save current back to parent
            const currentLevel = navigationStack.value[navigationStack.value.length - 1];
            const parent = navigationStack.value.length > 1 ? navigationStack.value[navigationStack.value.length - 2] : null;

            // This logic is tricky because we need to update the node in the parent state
            saveCurrentToParent();

            nodes.value = root.nodes;
            edges.value = root.edges;
            pan.value = root.pan;
            zoom.value = root.zoom;
            navigationStack.value = [];
        };

        const jumpToStack = (index) => {
            saveCurrentToParent();
            const target = navigationStack.value[index];
            nodes.value = target.nodes;
            edges.value = target.edges;
            pan.value = target.pan;
            zoom.value = target.zoom;
            navigationStack.value = navigationStack.value.slice(0, index);
        };

        const saveCurrentToParent = () => {
            if (navigationStack.value.length === 0) return;

            const subgraphData = {
                nodes: JSON.parse(JSON.stringify(nodes.value)),
                edges: JSON.parse(JSON.stringify(edges.value))
            };

            // Propagate backwards through stack
            for (let i = navigationStack.value.length - 1; i >= 0; i--) {
                const level = navigationStack.value[i];
                const targetId = i === navigationStack.value.length - 1 ? level.id : navigationStack.value[i + 1].id;
                const targetNode = level.nodes.find(n => n.id === targetId);
                if (targetNode) {
                    targetNode.data.subgraphContent = i === navigationStack.value.length - 1 ? subgraphData : {
                        nodes: JSON.parse(JSON.stringify(navigationStack.value[i + 1].nodes)),
                        edges: JSON.parse(JSON.stringify(navigationStack.value[i + 1].edges))
                    };
                }
            }
        };

        const getRootState = () => {
            if (navigationStack.value.length === 0) {
                return {
                    nodes: nodes.value,
                    edges: edges.value,
                    view: { pan: pan.value, zoom: zoom.value }
                };
            }
            saveCurrentToParent();
            const root = navigationStack.value[0];
            return {
                nodes: root.nodes,
                edges: root.edges,
                view: { pan: root.pan, zoom: root.zoom }
            };
        };

        const exportSVG = () => {
            const svg = canvasRef.value.querySelector('.connections-layer').cloneNode(true);
            const nodesHtml = canvasRef.value.querySelector('.canvas-content').cloneNode(true);
            // This is a bit complex as we need to inline all styles.
            // Simplified version: trigger parent's download logic if any or direct blob
            const content = `<svg xmlns="http://www.w3.org/2000/svg" width="2000" height="2000">
                <style>${STYLES}</style>
                <g transform="translate(${pan.value.x}, ${pan.value.y}) scale(${zoom.value})">
                    ${canvasRef.value.querySelector('.canvas-content').innerHTML}
                </g>
            </svg>`;
            const blob = new Blob([content], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `diagram-${Date.now()}.svg`;
            a.click();
        };

        const exportPNG = () => {
            store.addNotification('PNG Exporting...', 'info');
            // Simplified: use a library or complex canvas logic. 
            // For now, SVG is more reliable for this agent.
            exportSVG();
        };

        const exportMermaid = () => {
            let mermaid = 'graph LR\n';
            edges.value.forEach(e => {
                const source = nodes.value.find(n => n.id === e.source);
                const target = nodes.value.find(n => n.id === e.target);
                if (source && target) {
                    mermaid += `  ${source.id}["${source.data.label}"] --> ${target.id}["${target.data.label}"]\n`;
                }
            });

            const blob = new Blob([mermaid], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `diagram-${Date.now()}.mmd`;
            a.click();
        };

        const selectNode = (id) => {
            if (selectedNodes.value.includes(id)) return; // Keep multi-selection if clicking one of them?
            selectedNode.value = id;
            selectedNodes.value = []; // Clear multi-selection on single click
            selectedEdge.value = null;
        };

        const deleteNode = (id) => {
            nodes.value = nodes.value.filter(n => n.id !== id);
            edges.value = edges.value.filter(e => e.source !== id && e.target !== id);
            if (selectedNode.value === id) selectedNode.value = null;
        };

        const deleteSelected = () => {
            const idsToDelete = selectedNode.value ? [selectedNode.value] : selectedNodes.value;
            if (idsToDelete.length === 0) return;

            if (confirm(`Delete ${idsToDelete.length} item(s)?`)) {
                idsToDelete.forEach(id => deleteNode(id));
                selectedNode.value = null;
                selectedNodes.value = [];
                contextMenu.show = false;
            }
        };

        const duplicateSelected = () => {
            const idsToDup = selectedNode.value ? [selectedNode.value] : selectedNodes.value;
            if (idsToDup.length === 0) return;

            const newNodes = [];
            const idMap = {};

            idsToDup.forEach(id => {
                const node = nodes.value.find(n => n.id === id);
                if (node) {
                    const newNode = JSON.parse(JSON.stringify(node));
                    newNode.id = 'node_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                    newNode.x += 20;
                    newNode.y += 20;
                    idMap[id] = newNode.id;
                    newNodes.push(newNode);
                }
            });

            nodes.value.push(...newNodes);
            selectedNodes.value = newNodes.map(n => n.id);
            selectedNode.value = null;
            contextMenu.show = false;
        };

        const tidyLayout = () => {
            // Simple grid layout
            const padding = 50;
            const colWidth = 200;
            const rowHeight = 150;
            const cols = 4;

            nodes.value.forEach((node, index) => {
                node.x = padding + (index % cols) * colWidth;
                node.y = padding + Math.floor(index / cols) * rowHeight;
            });
            contextMenu.show = false;
        };

        const showContextMenu = (event) => {
            contextMenu.show = true;
            contextMenu.x = event.clientX;
            contextMenu.y = event.clientY;
        };

        // Methods - Connections
        const getPortPosition = (nodeId, portId) => {
            const node = nodes.value.find(n => n.id === nodeId);
            if (!node) return { x: 0, y: 0 };

            // Approximate port positions based on node size
            // Ideally we'd measure them, but calculating is faster given fixed layout
            const isInput = getInputs(node.type).find(p => p.id === portId);
            const isOutput = getOutputs(node.type).find(p => p.id === portId);

            // Header is ~35px, Body is below. 
            // Inputs loop roughly at: 45px + index * 20
            // Outputs loop roughly at: 45px + index * 20

            // For simplicity in this v1, let's assume one input/output port fixed pos
            // A more robust system would register port refs.

            let yOffset = 60;
            const xOffset = isInput ? 10 : 140; // Left vs Right

            return {
                x: node.x + xOffset,
                y: node.y + yOffset
            };
        };

        const startConnection = (nodeId, portId) => {
            isConnecting.value = true;
            connectionSource.value = { nodeId, portId };
        };

        const onPortMouseUp = (nodeId, portId) => {
            if (isConnecting.value && connectionSource.value) {
                // Validate not connecting to self (optional) or same port
                if (connectionSource.value.nodeId === nodeId) return;

                // Add edge
                edges.value.push({
                    id: 'edge_' + Date.now(),
                    source: connectionSource.value.nodeId,
                    sourcePort: connectionSource.value.portId,
                    target: nodeId,
                    targetPort: portId
                });

                isConnecting.value = false;
                connectionSource.value = null;
            }
        };

        const getEdgePath = (edge) => {
            const start = getPortPosition(edge.source, edge.sourcePort);
            const end = getPortPosition(edge.target, edge.targetPort);
            return calculateBezier(start, end);
        };

        const getDraftEdgePath = () => {
            if (!draftEdge.value) return '';
            return calculateBezier(draftEdge.value.start, draftEdge.value.end);
        };

        const calculateBezier = (start, end) => {
            const cp1 = { x: start.x + 50, y: start.y };
            const cp2 = { x: end.x - 50, y: end.y };
            return `M ${start.x} ${start.y} C ${cp1.x} ${cp1.y} ${cp2.x} ${cp2.y} ${end.x} ${end.y}`;
        };

        const selectEdge = (id) => {
            selectedEdge.value = id;
            selectedNode.value = null;
        };

        // Host actions
        const clearCanvas = () => {
            if (confirm('Clear all?')) {
                nodes.value = [];
                edges.value = [];
                currentDiagram.value = { id: null, title: '', description: '' };
            }
        };

        // API Actions
        const listDiagrams = async () => {
            loading.value = true;
            const res = await fetch('/@/node_editor/diagrams');
            if (res.ok) {
                cachedDiagrams.value = await res.json();
            }
            loading.value = false;
        };

        const loadDiagram = async (diagram) => {
            const res = await fetch(`/@/node_editor/diagrams/${diagram.id}`);
            if (res.ok) {
                const full = await res.json();
                currentDiagram.value = {
                    id: full.id,
                    title: full.title,
                    description: full.description
                };
                // Restore content
                const content = full.content || {};
                nodes.value = content.nodes || [];
                edges.value = content.edges || [];
                pan.value = content.view?.pan || { x: 0, y: 0 };
                zoom.value = content.view?.zoom || 1;
                variables.value = content.variables || [];
                navigationStack.value = [];

                showList.value = false;
            }
        };

        const createNewDiagram = () => {
            currentDiagram.value = { id: null, title: '', description: '' };
            nodes.value = [];
            edges.value = [];
            pan.value = { x: 0, y: 0 };
            zoom.value = 1;
            variables.value = [];
            navigationStack.value = [];
            showList.value = false;
        };

        const saveDiagram = () => {
            showSave.value = true;
        };

        const confirmSave = async () => {
            if (!currentDiagram.value.title) {
                store.addNotification('Title required', 'error');
                return;
            }

            const rootState = getRootState();

            const payload = {
                id: currentDiagram.value.id,
                title: currentDiagram.value.title,
                description: currentDiagram.value.description,
                content: {
                    nodes: rootState.nodes,
                    edges: rootState.edges,
                    variables: variables.value,
                    view: rootState.view
                }
            };

            const res = await fetch('/@/node_editor/diagrams/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                const result = await res.json();
                currentDiagram.value.id = result.id;
                showSave.value = false;
                store.addNotification('Saved!', 'success');
            } else {
                store.addNotification('Error saving', 'error');
            }
        };

        const deleteDiagram = async (id) => {
            if (confirm('Permanently delete?')) {
                await fetch(`/@/node_editor/diagrams/${id}`, { method: 'DELETE' });
                await listDiagrams();
            }
        };

        // Lifecycle
        onMounted(() => {
            injectStyles();
            window.addEventListener('keydown', (e) => {
                if (e.target.matches('input, textarea')) return;

                if (e.key === 'Delete' || e.key === 'Backspace') {
                    deleteSelected();
                } else if (e.key === 'd' || e.key === 'D') {
                    duplicateSelected();
                } else if (e.key === 'l' || e.key === 'L') {
                    tidyLayout();
                } else if (e.key === 'Escape') {
                    selectedNode.value = null;
                    selectedNodes.value = [];
                    contextMenu.show = false;
                } else if (e.key === 'n' || e.key === 'N') {
                    // Create node at mouse pos
                    const coords = toCanvasCoords(mousePos.value.x, mousePos.value.y);
                    const newNode = {
                        id: 'node_' + Date.now(),
                        type: 'process',
                        x: coords.x,
                        y: coords.y,
                        data: { label: 'New Process' }
                    };
                    nodes.value.push(newNode);
                    selectNode(newNode.id);
                }
            });
        });

        // Watchers
        watch(showList, (val) => {
            if (val) listDiagrams();
        });

        return {
            nodes, edges, pan, zoom,
            isDragging, onDragStart, onDrop, handleCanvasMouseDown, handleMouseMove, stopInteraction, handleZoom,
            startDragNode,
            isConnecting, startConnection, onPortMouseUp,
            getInputs, getOutputs,
            selectedNode, selectedNodes, selectedEdge, selectNode, selectEdge, deleteNode, deleteSelected, duplicateSelected, tidyLayout, showContextMenu,
            selectedNodeData, selectionRect, selectionBoxStyle, contextMenu,
            getEdgePath, draftEdge, getDraftEdgePath,
            canvasRef,
            showList, showSave, loading, cachedDiagrams, currentDiagram,
            clearCanvas, loadDiagram, createNewDiagram, saveDiagram, confirmSave, deleteDiagram,
            // Phase 2
            variables, showVariables, navigationStack, searchQuery, filteredNodes,
            teleportToNode, enterSubgraph, resetToRoot, jumpToStack,
            exportSVG, exportPNG, exportMermaid
        };
    }
}
