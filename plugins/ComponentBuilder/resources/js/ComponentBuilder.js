import { ref, reactive, onMounted, computed, defineAsyncComponent } from 'vue';

// Lazy load builder sub-components from core builders
const ComponentToolbox = defineAsyncComponent(() => import('builders/builder/ComponentToolbox.js'));
const ComponentCanvas = defineAsyncComponent(() => import('builders/builder/ComponentCanvas.js'));
const ComponentProperties = defineAsyncComponent(() => import('builders/builder/ComponentProperties.js'));
const ComponentTree = defineAsyncComponent(() => import('builders/builder/ComponentTree.js'));
const LucideIcon = defineAsyncComponent(() => import('ui/Icon.js'));
const Modal = defineAsyncComponent(() => import('ui/Modal.js'));

export default {
    name: 'ComponentBuilder',
    components: {
        ComponentToolbox,
        ComponentCanvas,
        ComponentProperties,
        ComponentProperties,
        ComponentTree,
        LucideIcon,
        Modal
    },
    template: `
        <div class="component-builder">
            <div class="builder-header">
                <div style="display:flex; align-items:center;">
                    <button class="btn btn-icon" @click="$emit('back')" style="margin-right: 15px;">
                        <LucideIcon name="arrow-left" size="20" />
                    </button>
                    <h1>Component Builder</h1>
                </div>
                <div class="builder-actions">
                    <button class="btn btn-secondary" @click="previewComponent" :disabled="loading">
                        <LucideIcon name="eye" size="18" /> Preview
                    </button>
                    <button class="btn" :class="saved ? 'btn-success' : 'btn-primary'" @click="saveComponent" :disabled="loading" style="min-width: 160px;">
                        <span v-if="loading" class="spinner-sm"></span>
                        <LucideIcon v-else :name="saved ? 'check' : 'save'" size="18" />
                        {{ saved ? 'Saved!' : 'Save Component' }}
                    </button>
                </div>
            </div>

            <div class="builder-workspace">
                <!-- Left Sidebar: Toolbox -->
                <div class="builder-sidebar left">
                    <div class="sidebar-header">
                        <h3>Toolbox</h3>
                    </div>
                    <div class="sidebar-content">
                        <ComponentToolbox @add-component="addComponent" />
                    </div>
                </div>
                
                <!-- Center: Tabs + Content -->
                <div class="builder-center">
                    <div class="builder-tabs">
                        <button 
                            class="tab-btn" 
                            :class="{ active: activeTab === 'layout' }"
                            @click="activeTab = 'layout'"
                        >
                            <LucideIcon name="layout" size="18" /> Layout
                        </button>
                        <button 
                            class="tab-btn" 
                            :class="{ active: activeTab === 'structure' }"
                            @click="activeTab = 'structure'"
                        >
                            <LucideIcon name="list" size="18" /> Structure
                        </button>
                    </div>

                    <div class="builder-content-area">
                        <!-- Layout View (Canvas) -->
                        <div v-show="activeTab === 'layout'" class="view-layout">
                            <div class="builder-canvas-wrapper">
                                <ComponentCanvas 
                                    :layout="component.layout"
                                    :selected-id="selectedComponentId"
                                    @select="selectComponent"
                                    @update="updateLayout"
                                />
                            </div>
                        </div>

                        <!-- Structure View (Tree) -->
                        <div v-show="activeTab === 'structure'" class="view-structure">
                            <div class="tree-view-wrapper">
                                <ComponentTree 
                                    :layout="component.layout"
                                    :selected-id="selectedComponentId"
                                    @select="selectComponent"
                                    @move="moveComponent"
                                    @add="handleTreeAdd"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Sidebar: Properties -->
                <div class="builder-sidebar right">
                    <ComponentProperties 
                        :component="selectedComponent"
                        @update="updateComponentProperty"
                        @remove="removeComponent"
                    />
                </div>
            </div>
            
            <!-- Metadata Modal -->
            <Modal :show="showMetadataModal" title="Component Settings" @close="showMetadataModal = false">
                <div class="form-group">
                    <label>Name (ID)</label>
                    <input v-model="component.name" type="text" placeholder="user-stats" pattern="[a-z0-9-]+" required>
                    <small>Unique identifier, lowercase with hyphens</small>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input v-model="component.title" type="text" placeholder="User Statistics" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea v-model="component.description"></textarea>
                </div>
                <div class="form-group">
                    <label>Icon</label>
                    <input v-model="component.icon" type="text" placeholder="puzzle">
                </div>
                <div class="modal-footer" style="margin-top:20px; text-align:right;">
                    <button class="btn" @click="showMetadataModal = false">Cancel</button>
                    <button class="btn btn-primary" @click="saveMetadata">Continue</button>
                </div>
            </Modal>
        </div>
    `,
    props: ['initialId'],
    emits: ['back'],
    setup(props, { emit }) {

        const loading = ref(false);
        const saved = ref(false);
        const showMetadataModal = ref(false);
        const selectedComponentId = ref(null);
        const activeTab = ref('structure');

        const component = reactive({
            id: null,
            name: '',
            title: '',
            description: '',
            icon: 'puzzle',
            layout: {
                type: 'container',
                children: []
            },
            dataSources: {},
            actions: {}
        });

        onMounted(async () => {
            if (props.initialId) {
                loading.value = true;
                try {
                    const res = await fetch(`/@/admin/component-builder/${props.initialId}`);
                    if (res.ok) {
                        const data = await res.json();
                        Object.assign(component, data);

                        // Extract fields from definition if present (since DB stores them inside definition)
                        if (data.definition) {
                            if (data.definition.layout) component.layout = data.definition.layout;
                            if (data.definition.dataSources) component.dataSources = data.definition.dataSources;
                            if (data.definition.actions) component.actions = data.definition.actions;
                        }

                        // Parse layout if it's somehow still a string
                        if (typeof component.layout === 'string') {
                            try { component.layout = JSON.parse(component.layout); } catch (e) { }
                        }
                    }
                } catch (e) {
                    console.error('Failed to load component', e);
                } finally {
                    loading.value = false;
                }
            }
        });

        const selectedComponent = computed(() => {
            if (!selectedComponentId.value) return null;
            return findComponentById(component.layout, selectedComponentId.value);
        });

        const findComponentById = (node, id) => {
            if (node.id === id) return node;
            if (node.children) {
                for (const child of node.children) {
                    const found = findComponentById(child, id);
                    if (found) return found;
                }
            }
            return null;
        };

        const createComponent = (type) => {
            return {
                id: 'comp_' + Math.random().toString(36).substr(2, 9),
                type: type,
                label: `New ${type}`,
                props: {},
                children: []
            };
        };

        const addComponent = (type) => {
            const newComponent = createComponent(type);
            component.layout.children.push(newComponent);
            selectComponent(newComponent.id);
        };

        const handleTreeAdd = ({ type, targetId, placement }) => {
            const newComponent = createComponent(type);
            const targetNode = findComponentById(component.layout, targetId);

            if (!targetNode) return;

            if (placement === 'inside') {
                // Check if container logic
                const containers = ['container', 'row', 'col', 'form'];
                if (!containers.includes(targetNode.type) && targetNode.id !== component.layout.id) {
                    alert(`Cannot drop inside ${targetNode.type}`);
                    return;
                }
                if (!targetNode.children) targetNode.children = [];
                targetNode.children.push(newComponent);
            } else {
                // Before/After
                // Find parent
                const findParent = (root, childId) => {
                    if (root.children) {
                        if (root.children.some(c => c.id === childId)) return root;
                        for (const c of root.children) {
                            const res = findParent(c, childId);
                            if (res) return res;
                        }
                    }
                    return null;
                };
                const parent = findParent(component.layout, targetId);

                if (parent) {
                    const idx = parent.children.findIndex(c => c.id === targetId);
                    if (placement === 'before') {
                        parent.children.splice(idx, 0, newComponent);
                    } else {
                        parent.children.splice(idx + 1, 0, newComponent);
                    }
                } else {
                    // Fallback
                    component.layout.children.push(newComponent);
                }
            }

            updateLayout({ ...component.layout });
            selectComponent(newComponent.id);
        };

        const selectComponent = (id) => {
            selectedComponentId.value = id;
        };

        const updateLayout = (newLayout) => {
            component.layout = newLayout;
        };

        // Move Logic: Remove from old parent, add to new parent
        const moveComponent = ({ sourceId, targetId, placement }) => {
            console.log('Moving', sourceId, 'to', targetId, placement);

            if (sourceId === component.layout.id) return;
            if (sourceId === targetId) return;

            // 1. Find source and remove it
            let sourceNode = null;
            let sourceParent = null;

            // Pass 1: Find Source
            const findSourceParent = (node) => {
                if (node.children) {
                    const child = node.children.find(c => c.id === sourceId);
                    if (child) return node;
                    for (const c of node.children) {
                        const res = findSourceParent(c);
                        if (res) return res;
                    }
                }
                return null;
            };
            sourceParent = findSourceParent(component.layout);
            if (sourceParent) {
                sourceNode = sourceParent.children.find(c => c.id === sourceId);
            }
            if (!sourceNode) return;

            // Pass 2: Find Target
            const targetNode = findComponentById(component.layout, targetId);
            if (!targetNode) return;

            // Check Ancestry (Target cannot be inside Source)
            const isAncestor = (ancestor, descendantId) => {
                if (ancestor.id === descendantId) return true;
                if (ancestor.children) {
                    return ancestor.children.some(c => isAncestor(c, descendantId));
                }
                return false;
            };
            if (isAncestor(sourceNode, targetId)) {
                alert('Cannot move component inside itself');
                return;
            }

            // Remove from old parent
            sourceParent.children = sourceParent.children.filter(c => c.id !== sourceId);

            // Insert into new location
            if (placement === 'inside') {
                if (!targetNode.children) targetNode.children = [];
                targetNode.children.push(sourceNode);
            } else {
                // Find parent of target to insert before/after
                const findParent = (root, childId) => {
                    if (root.children) {
                        if (root.children.some(c => c.id === childId)) return root;
                        for (const c of root.children) {
                            const res = findParent(c, childId);
                            if (res) return res;
                        }
                    }
                    return null;
                };

                const targetParentNode = findParent(component.layout, targetId);

                if (targetParentNode) {
                    const targetIdx = targetParentNode.children.findIndex(c => c.id === targetId);
                    if (placement === 'before') {
                        targetParentNode.children.splice(targetIdx, 0, sourceNode);
                    } else {
                        targetParentNode.children.splice(targetIdx + 1, 0, sourceNode);
                    }
                } else {
                    component.layout.children.push(sourceNode);
                }
            }

            updateLayout({ ...component.layout }); // Trigger reactivity
            selectComponent(sourceId);
        };

        const removeComponent = (id) => {
            if (id === component.layout.id) {
                alert('Cannot remove the root component.');
                return;
            }

            // Recursive finder for parent
            const findParent = (root, childId) => {
                if (root.children) {
                    if (root.children.some(c => c.id === childId)) return root;
                    for (const c of root.children) {
                        const res = findParent(c, childId);
                        if (res) return res;
                    }
                }
                return null;
            };

            const parent = findParent(component.layout, id);

            if (parent) {
                parent.children = parent.children.filter(c => c.id !== id);
                // If the removed component was selected, deselect it
                if (selectedComponentId.value === id) {
                    selectedComponentId.value = null;
                }
                updateLayout({ ...component.layout });
            }
        };

        const updateComponentProperty = ({ id, key, value }) => {
            const comp = findComponentById(component.layout, id);
            if (comp) {
                if (key.startsWith('props.')) {
                    // Ensure props object exists
                    if (!comp.props) comp.props = {};
                    comp.props[key.split('.')[1]] = value;
                } else if (key === 'label') {
                    comp.label = value;
                } else {
                    comp[key] = value;
                }
            }
        };

        const saveComponent = async () => {
            if (!component.name) {
                showMetadataModal.value = true;
                return;
            }

            loading.value = true;
            try {
                const payload = {
                    ...component,
                    definition: { ...component } // Send full object as definition
                };

                let url = '/@/admin/component-builder';
                let method = 'POST';

                if (component.id) {
                    url = `/@/admin/component-builder/${component.id}`;
                    method = 'PUT';
                }

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (data.error) throw new Error(data.error);

                // If created, update local ID
                if (data.id && !component.id) {
                    component.id = data.id;
                }

                // Trigger generation
                await fetch(`/@/admin/component-builder/${component.id}/generate`, { method: 'POST' });

                // Show success state
                saved.value = true;
                setTimeout(() => {
                    saved.value = false;
                }, 2000);

            } catch (e) {
                alert('Error saving component: ' + e.message);
            } finally {
                loading.value = false;
            }
        };

        const saveMetadata = () => {
            if (component.name && component.title) {
                showMetadataModal.value = false;
                saveComponent();
            } else {
                alert('Please fill in Name and Title to continue.');
            }
        };

        const previewComponent = () => {
            if (!component.name) {
                alert('Please give the component a name first.');
                return;
            }
            // Use view_name if available, fallback to name (sanitized)
            const view = component.view_name || component.name;
            window.open(`/component-preview/${view}`, '_blank');
        };

        return {
            component,
            loading,
            saved,
            showMetadataModal,
            selectedComponentId,
            activeTab,
            selectedComponent,
            addComponent,
            selectComponent,
            moveComponent,
            handleTreeAdd,
            updateLayout,
            updateLayout,
            updateComponentProperty,
            removeComponent,
            saveComponent,
            saveMetadata,
            previewComponent
        };
    },
    styles: `
        .component-builder {
            height: calc(100vh - 60px); 
            display: flex;
            flex-direction: column;
        }
    `
};
