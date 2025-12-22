import { ref, onMounted, computed, reactive, watch } from 'vue';
import Icon from 'ui/Icon.js';

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
            <div class="button-group">
                <button @click="saveDiagram" class="btn btn-primary">
                    <LucideIcon name="save" size="16" />
                    Save
                </button>
                <button @click="showList = true" class="btn btn-secondary">
                    <LucideIcon name="list" size="16" />
                    Load
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
                <h3>Nodes</h3>
                <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'process')"
                >
                    <LucideIcon name="cpu" size="16" />
                    Process Node
                </div>
                <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'input')"
                >
                    <LucideIcon name="log-in" size="16" />
                    Input Node
                </div>
                <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'output')"
                >
                    <LucideIcon name="log-out" size="16" />
                    Output Node
                </div>
                 <div 
                    class="node-item" 
                    draggable="true" 
                    @dragstart="onDragStart($event, 'note')"
                >
                    <LucideIcon name="sticky-note" size="16" />
                    Note
                </div>
            </div>

            <!-- Canvas -->
            <div 
                class="editor-canvas" 
                ref="canvasRef"
                @drop="onDrop" 
                @dragover.prevent
                @mousedown="startPan"
                @mousemove="handleMouseMove"
                @mouseup="stopInteraction"
                @wheel.prevent="handleZoom"
            >
                <div 
                    class="canvas-content"
                    :style="{ transform: 'translate(' + pan.x + 'px, ' + pan.y + 'px) scale(' + zoom + ')' }"
                >
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
                        :class="['node-' + node.type, { selected: selectedNode === node.id }]"
                        :style="{ left: node.x + 'px', top: node.y + 'px' }"
                        @mousedown.stop="startDragNode($event, node)"
                        @click.stop="selectNode(node.id)"
                    >
                        <div class="node-header">
                            <span class="node-title">{{ node.data.label || node.type }}</span>
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
                 <div class="form-group" v-if="selectedNodeData.type === 'note'">
                    <label>Content</label>
                    <textarea v-model="selectedNodeData.data.content" class="form-input" rows="5"></textarea>
                </div>
                 <div class="form-group">
                    <label>ID</label>
                    <input :value="selectedNodeData.id" class="form-input" disabled />
                </div>
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
        
    </div>
    `,
    /* CSS Injection */
    styles: `
        .node-editor-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 100px); /* Adjust based on admin header */
            background: #f8f9fa;
        }
        .editor-header {
            padding: 1rem;
            background: white;
            border-bottom: 1px solid #e5e7eb;
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
            background: white;
            border-right: 1px solid #e5e7eb;
            padding: 1rem;
            z-index: 10;
        }
        .editor-properties {
            width: 250px;
            background: white;
            border-left: 1px solid #e5e7eb;
            padding: 1rem;
            z-index: 10;
        }
        .node-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: grab;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .node-item:hover {
            background: #e5e7eb;
        }
        .editor-canvas {
            flex: 1;
            position: relative;
            overflow: hidden;
            background-color: #f8f9fa;
            background-image: radial-gradient(#d1d5db 1px, transparent 1px);
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
            stroke: #9ca3af;
            stroke-width: 2;
            pointer-events: stroke; /* Allow clicking on the line itself */
            cursor: pointer;
        }
        .connection-line:hover {
            stroke: #6b7280;
            stroke-width: 3;
        }
        .connection-line.selected {
            stroke: #3b82f6;
            stroke-width: 3;
        }
         .connection-line.draft {
            stroke: #3b82f6;
            stroke-dasharray: 4;
        }
        .node-rect {
            position: absolute;
            width: 150px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            user-select: none;
        }
        .node-rect.selected {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        .node-header {
            padding: 0.5rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
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
            color: #6b7280;
            cursor: pointer;
        }
        .port-dot {
            width: 8px;
            height: 8px;
            background: #9ca3af;
            border-radius: 50%;
        }
        .port:hover .port-dot {
            background: #3b82f6;
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
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 100;
        }
        .modal {
            background: white;
            border-radius: 8px;
            width: 500px;
            max-width: 90vw;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .modal-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
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
            border-top: 1px solid #e5e7eb;
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
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .diagram-list li:hover {
            background: #f9fafb;
        }
        .diagram-title { font-weight: 500; }
        .diagram-date { font-size: 0.8em; color: gray; margin-right: 10px; }
        
        /* Node Type Specifics */
        .node-process .node-header { background-color: #e0f2fe; color: #0369a1; }
        .node-input .node-header { background-color: #dcfce7; color: #15803d; }
        .node-output .node-header { background-color: #fee2e2; color: #b91c1c; }
        .node-note .node-header { background-color: #fef9c3; color: #a16207; }
        .node-note .node-rect { background-color: #fefce8; width: 200px; }
    `,
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
        const selectedEdge = ref(null);

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

        // Ref
        const canvasRef = ref(null);

        // Methods - Canvas
        const injectStyles = () => {
            const styleId = 'node-editor-styles';
            if (!document.getElementById(styleId)) {
                const style = document.createElement('style');
                style.id = styleId;
                // Just use the strings directly, assuming `this.$options.styles` is accessible or passed
                // But in setup() we don't have this. 
                // We'll trust the component architecture to handle this or brute force it.
                // For this single-file setup, I'll basically just copy the string here or put it in a variable outside setup.
                style.textContent = `
                    .node-editor-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 100px); /* Adjust based on admin header */
            background: #f8f9fa;
        }
        .editor-header {
            padding: 1rem;
            background: white;
            border-bottom: 1px solid #e5e7eb;
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
            background: white;
            border-right: 1px solid #e5e7eb;
            padding: 1rem;
            z-index: 10;
        }
        .editor-properties {
            width: 250px;
            background: white;
            border-left: 1px solid #e5e7eb;
            padding: 1rem;
            z-index: 10;
        }
        .node-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: grab;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .node-item:hover {
            background: #e5e7eb;
        }
        .editor-canvas {
            flex: 1;
            position: relative;
            overflow: hidden;
            background-color: #f8f9fa;
            background-image: radial-gradient(#d1d5db 1px, transparent 1px);
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
            stroke: #9ca3af;
            stroke-width: 2;
            pointer-events: stroke; /* Allow clicking on the line itself */
            cursor: pointer;
        }
        .connection-line:hover {
            stroke: #6b7280;
            stroke-width: 3;
        }
        .connection-line.selected {
            stroke: #3b82f6;
            stroke-width: 3;
        }
         .connection-line.draft {
            stroke: #3b82f6;
            stroke-dasharray: 4;
        }
        .node-rect {
            position: absolute;
            width: 150px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            user-select: none;
        }
        .node-rect.selected {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        .node-header {
            padding: 0.5rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
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
            color: #6b7280;
            cursor: pointer;
        }
        .port-dot {
            width: 8px;
            height: 8px;
            background: #9ca3af;
            border-radius: 50%;
        }
        .port:hover .port-dot {
            background: #3b82f6;
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
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 100;
        }
        .modal {
            background: white;
            border-radius: 8px;
            width: 500px;
            max-width: 90vw;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .modal-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
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
            border-top: 1px solid #e5e7eb;
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
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .diagram-list li:hover {
            background: #f9fafb;
        }
        .diagram-title { font-weight: 500; }
        .diagram-date { font-size: 0.8em; color: gray; margin-right: 10px; }
        
        /* Node Type Specifics */
        .node-process .node-header { background-color: #e0f2fe; color: #0369a1; }
        .node-input .node-header { background-color: #dcfce7; color: #15803d; }
        .node-output .node-header { background-color: #fee2e2; color: #b91c1c; }
        .node-note .node-header { background-color: #fef9c3; color: #a16207; }
        .node-note .node-rect { background-color: #fefce8; width: 200px; }
                `;
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

        const startPan = (event) => {
            if (event.button === 0 && !event.target.closest('.node-rect') && !event.target.closest('.connection-line') && !isConnecting.value) {
                isDragging.value = true;
                dragStart.value = { x: event.clientX - pan.value.x, y: event.clientY - pan.value.y };
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

            if (isConnecting.value) {
                // Cancel connection if dropped on nothing
                // But wait, validation is done in onPortMouseUp
                isConnecting.value = false;
                connectionSource.value = null;
            }
        };

        // Methods - Nodes & Ports
        const getInputs = (type) => {
            if (type === 'process') return [{ id: 'in', label: 'In' }];
            if (type === 'output') return [{ id: 'in', label: 'Value' }];
            return [];
        };

        const getOutputs = (type) => {
            if (type === 'process') return [{ id: 'out', label: 'Out' }];
            if (type === 'input') return [{ id: 'out', label: 'Value' }];
            return [];
        };

        const selectNode = (id) => {
            selectedNode.value = id;
            selectedEdge.value = null;
        };

        const deleteNode = (id) => {
            if (confirm('Delete node?')) {
                nodes.value = nodes.value.filter(n => n.id !== id);
                edges.value = edges.value.filter(e => e.source !== id && e.target !== id);
                selectedNode.value = null;
            }
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
            // Fetch full details if needed, but we probably listing summaries
            // Assuming we need to fetch individual content
            const res = await fetch(`/@/node_editor/diagrams/${diagram.id}`);
            if (res.ok) {
                const full = await res.json();
                currentDiagram.value = {
                    id: full.id,
                    title: full.title,
                    description: full.description
                };
                // Restore content
                const content = full.content || {}; // object or string handled by controller
                nodes.value = content.nodes || [];
                edges.value = content.edges || [];
                pan.value = content.view?.pan || { x: 0, y: 0 };
                zoom.value = content.view?.zoom || 1;

                showList.value = false;
            }
        };

        const createNewDiagram = () => {
            currentDiagram.value = { id: null, title: '', description: '' };
            nodes.value = [];
            edges.value = [];
            pan.value = { x: 0, y: 0 };
            zoom.value = 1;
            showList.value = false;
        };

        const saveDiagram = () => {
            showSave.value = true;
        };

        const confirmSave = async () => {
            if (!currentDiagram.value.title) {
                alert('Title required');
                return;
            }

            const payload = {
                id: currentDiagram.value.id,
                title: currentDiagram.value.title,
                description: currentDiagram.value.description,
                content: {
                    nodes: nodes.value,
                    edges: edges.value,
                    view: { pan: pan.value, zoom: zoom.value }
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
                alert('Saved!');
            } else {
                alert('Error saving');
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
            // Listen for keydown delete?
            window.addEventListener('keydown', (e) => {
                if ((e.key === 'Delete' || e.key === 'Backspace') && !e.target.matches('input, textarea')) {
                    if (selectedNode.value) deleteNode(selectedNode.value);
                    // if (selectedEdge.value) ...
                }
            });
        });

        // Watchers
        watch(showList, (val) => {
            if (val) listDiagrams();
        });

        return {
            nodes, edges, pan, zoom,
            isDragging, onDragStart, onDrop, startPan, handleMouseMove, stopInteraction, handleZoom,
            startDragNode,
            isConnecting, startConnection, onPortMouseUp,
            getInputs, getOutputs,
            selectedNode, selectedEdge, selectNode, selectEdge, deleteNode,
            selectedNodeData,
            getEdgePath, draftEdge, getDraftEdgePath,
            canvasRef,
            showList, showSave, loading, cachedDiagrams, currentDiagram,
            clearCanvas, loadDiagram, createNewDiagram, saveDiagram, confirmSave, deleteDiagram
        };
    }
}
