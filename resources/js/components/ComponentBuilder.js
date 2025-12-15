import { ref, reactive, onMounted, computed, defineAsyncComponent } from 'vue';

// Lazy load builder sub-components
const ComponentToolbox = defineAsyncComponent(() => import('./builder/ComponentToolbox.js'));
const ComponentCanvas = defineAsyncComponent(() => import('./builder/ComponentCanvas.js'));
const ComponentProperties = defineAsyncComponent(() => import('./builder/ComponentProperties.js'));

export default {
    name: 'ComponentBuilder',
    components: {
        ComponentToolbox,
        ComponentCanvas,
        ComponentProperties
    },
    template: `
        <div class="component-builder">
            <div class="builder-header">
                <h1>Component Builder</h1>
                <div class="builder-actions">
                    <button class="btn btn-secondary" @click="previewComponent" :disabled="loading">
                        <i class="icon-eye"></i> Preview
                    </button>
                    <button class="btn btn-primary" @click="saveComponent" :disabled="loading">
                        <span v-if="loading" class="spinner-sm"></span>
                        <i v-else class="icon-save"></i> Save Component
                    </button>
                </div>
            </div>

            <div class="builder-workspace">
                <div class="builder-sidebar left">
                    <ComponentToolbox @add-component="addComponent" />
                </div>
                
                <div class="builder-canvas-wrapper">
                    <ComponentCanvas 
                        :layout="component.layout"
                        :selected-id="selectedComponentId"
                        @select="selectComponent"
                        @update="updateLayout"
                    />
                </div>
                
                <div class="builder-sidebar right">
                    <ComponentProperties 
                        :component="selectedComponent"
                        @update="updateComponentProperty"
                    />
                </div>
            </div>
            
            <!-- Metadata Modal -->
            <div v-if="showMetadataModal" class="modal active">
                <div class="modal-overlay" @click="showMetadataModal = false"></div>
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>Component Settings</h3>
                        <button class="close-btn" @click="showMetadataModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
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
                    </div>
                    <div class="modal-footer">
                        <button class="btn" @click="showMetadataModal = false">Cancel</button>
                        <button class="btn btn-primary" @click="saveMetadata">Continue</button>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup() {
        const loading = ref(false);
        const showMetadataModal = ref(false);
        const selectedComponentId = ref(null);
        
        const component = reactive({
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

        const selectedComponent = computed(() => {
            if (!selectedComponentId.value) return null;
            return findComponentById(component.layout, selectedComponentId.value);
        });

        // Helper to find component in tree
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

        const addComponent = (type) => {
            const newComponent = {
                id: 'comp_' + Math.random().toString(36).substr(2, 9),
                type: type,
                label: `New ${type}`,
                props: {},
                children: [] // Containers might have children
            };
            
            // For now, just add to root container
            component.layout.children.push(newComponent);
            selectComponent(newComponent.id);
        };

        const selectComponent = (id) => {
            selectedComponentId.value = id;
        };

        const updateLayout = (newLayout) => {
            component.layout = newLayout;
        };

        const updateComponentProperty = ({ id, key, value }) => {
            const comp = findComponentById(component.layout, id);
            if (comp) {
                if (key.startsWith('props.')) {
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
                
                const response = await fetch('/@/admin/component-builder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                
                // Trigger generation
                await fetch(`/@/admin/component-builder/${data.id}/generate`, { method: 'POST' });
                
                alert('Component saved successfully!');
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
             }
        };

        const previewComponent = () => {
             // Logic to open preview
             alert('Preview not implemented yet');
        };

        return {
            component,
            loading,
            showMetadataModal,
            selectedComponentId,
            selectedComponent,
            addComponent,
            selectComponent,
            updateLayout,
            updateComponentProperty,
            saveComponent,
            saveMetadata,
            previewComponent
        };
    }
};
