import { ref, watch, computed, provide, inject, reactive } from 'vue';
import TreeItem from './common/TreeItem.js';
import PreviewRenderer from './common/PreviewRenderer.js';
import { TreeHelper } from '@/utils/TreeHelper.js';

export default {
    components: { TreeItem, PreviewRenderer },
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: `
    <div class="template-builder-v2" style="display: flex; gap: 20px; height: 700px; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-primary);">
        
        <!-- TOOLBOX (Left Sidebar) -->
        <div class="toolbox" style="width: 200px; background: var(--card-bg); border-right: 1px solid var(--border-color); padding: 15px; display: flex; flex-direction: column;">
            <h4 style="margin-bottom: 15px; font-size: 0.9em; text-transform: uppercase; color: var(--text-secondary);">Components</h4>
            
            <div class="tool-category" style="margin-bottom: 20px;">
                <label style="font-size: 0.8em; font-weight: bold; color: var(--text-secondary); margin-bottom: 8px; display: block; text-transform: uppercase;">Structure</label>
                <div class="toolbox-item" draggable="true" @dragstart="onToolDragStart($event, 'section')">Section</div>
                <div class="toolbox-item" draggable="true" @dragstart="onToolDragStart($event, 'columns')">Columns (1-12)</div>
            </div>

            <div class="tool-category">
                <label style="font-size: 0.8em; font-weight: bold; color: var(--text-secondary); margin-bottom: 8px; display: block; text-transform: uppercase;">Content</label>
                <div class="toolbox-item" draggable="true" @dragstart="onToolDragStart($event, 'h1')">Heading 1</div>
                <div class="toolbox-item" draggable="true" @dragstart="onToolDragStart($event, 'h2')">Heading 2</div>
                <div class="toolbox-item" draggable="true" @dragstart="onToolDragStart($event, 'p')">Paragraph</div>
                <div class="toolbox-item" draggable="true" @dragstart="onToolDragStart($event, 'image')">Image</div>
            </div>

            <div v-if="selectedItem" class="properties-panel" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <h4 style="margin-bottom: 10px; font-size: 0.9em; text-transform: uppercase; color: var(--text-secondary);">Properties</h4>
                
                <div v-if="selectedItem.type === 'columns'" class="prop-group">
                    <label style="display:block; font-size:0.8em; margin-bottom:5px;">Columns (1-12)</label>
                    <input type="number" min="1" max="12" :value="selectedItem.children.length" @input="updateColumnCount($event.target.value)" style="width:100%; padding:5px; background:rgba(0,0,0,0.2); border:1px solid var(--border-color); color:var(--text-primary); border-radius:4px;">
                </div>

                <!-- Common Properties: Slot Name -->
                <div class="prop-group" style="margin-top: 10px;">
                    <label style="display:block; font-size:0.8em; margin-bottom:5px;">Slot Name (for Page Editor)</label>
                    <input type="text" v-model="selectedItem.slotName" placeholder="e.g. Main Headline" style="width:100%; padding:5px; background:rgba(0,0,0,0.2); border:1px solid var(--border-color); color:var(--text-primary); border-radius:4px;">
                </div>

                <!-- Text Content Properties -->
                <div v-if="['h1','h2','h3','p'].includes(selectedItem.type)" class="prop-group" style="margin-top: 10px;">
                    <label style="display:block; font-size:0.8em; margin-bottom:5px;">Text Content</label>
                    <textarea v-model="selectedItem.content" rows="3" style="width:100%; padding:5px; background:rgba(0,0,0,0.2); border:1px solid var(--border-color); color:var(--text-primary); border-radius:4px;"></textarea>
                </div>
                
                <!-- Image Properties -->
                <div v-if="selectedItem.type === 'image'" class="prop-group" style="margin-top: 10px;">
                    <label style="display:block; font-size:0.8em; margin-bottom:5px;">Image URL</label>
                    <input type="text" v-model="selectedItem.src" placeholder="/uploads/..." style="width:100%; padding:5px; background:rgba(0,0,0,0.2); border:1px solid var(--border-color); color:var(--text-primary); border-radius:4px;">
                </div>

                <div v-if="!['columns', 'h1', 'h2', 'h3', 'p', 'image'].includes(selectedItem.type)" style="font-size:0.8em; color:var(--text-muted); font-style:italic; margin-top:10px;">
                    No specific properties for {{ selectedItem.type }}
                </div>
            </div>

            <div style="margin-top: auto; padding-top: 10px; border-top: 1px solid var(--border-color);">
                <button v-if="selectedPath" @click="removeSelected" class="btn-xs btn-danger" style="width: 100%;">Delete Selected</button>
            </div>
        </div>

        <!-- TREE VIEW (Center) -->
        <div class="structure-tree" style="width: 300px; background: rgba(0,0,0,0.1); border-right: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div class="panel-header" style="padding: 10px; background: var(--card-bg); border-bottom: 1px solid var(--border-color);">
                <h4 style="margin: 0; font-size: 0.9em; text-transform: uppercase;">Structure Tree</h4>
            </div>
            <div class="tree-content" style="flex: 1; overflow-y: auto; padding: 10px;">
                
                <!-- Explicit Root Regions -->
                <div v-for="region in ['header', 'main', 'footer']" :key="region">
                    <div class="root-node"
                         @dragover.prevent="onRootDragOver($event, region)"
                         @drop="onRootDrop($event, region)"
                         :class="{ 'drop-active': dragState.targetPath === region }"
                         style="padding: 5px; margin-bottom: 10px; border: 1px dashed transparent;">
                        
                        <h5 style="margin: 0 0 5px 0; font-size: 0.8em; text-transform: uppercase; opacity: 0.5;">{{ region }}</h5>
                        
                        <div v-if="structure[region].length === 0" style="font-style: italic; font-size: 0.8em; color: var(--text-secondary); padding: 5px;">
                            Empty (Drop here)
                        </div>

                        <TreeItem 
                            v-for="(item, idx) in structure[region]" 
                            :key="idx" 
                            :element="item" 
                            :path="region + '.' + idx"
                            :index="idx"
                        />
                    </div>
                </div>

            </div>
        </div>

        <!-- PREVIEW (Right) -->
        <div class="preview-pane" style="flex: 1; display: flex; flex-direction: column; background: white; color: black;">
            <div class="panel-header" style="padding: 10px; background: #f0f0f0; border-bottom: 1px solid #ccc;">
                <h4 style="margin: 0; font-size: 0.9em; text-transform: uppercase; color: #333;">Live Preview</h4>
            </div>
            <div class="preview-content" style="flex: 1; overflow-y: auto; padding: 20px;">
                <div class="preview-wrapper">
                    <PreviewRenderer :element="{ type: 'header', children: structure.header }" />
                    <PreviewRenderer :element="{ type: 'main', children: structure.main }" />
                    <PreviewRenderer :element="{ type: 'footer', children: structure.footer }" />
                </div>
            </div>
        </div>

    </div>
    `,

    setup(props, { emit }) {
        const structure = ref({ header: [], main: [], footer: [] });
        const selectedPath = ref(null);

        // DragState is now managed by TreeItem mostly, but we need it for root drop zones
        // Create a local reactive state for root drop feedback
        const dragState = reactive({ targetPath: null });

        // Load initial data
        watch(() => props.modelValue, (val) => {
            if (val && val !== JSON.stringify(structure.value)) {
                try {
                    const parsed = typeof val === 'string' ? JSON.parse(val) : val;
                    if (parsed) {
                        structure.value = {
                            header: parsed.header || [],
                            main: parsed.main || [],
                            footer: parsed.footer || []
                        };
                    }
                } catch (e) { console.error(e); }
            }
        }, { immediate: true });

        // Auto save
        watch(structure, (val) => {
            emit('update:modelValue', JSON.stringify(val));
        }, { deep: true });

        // --- Drag Helpers ---
        const onToolDragStart = (e, type) => {
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('sourceInfo', JSON.stringify({ type: 'NEW', component: type }));
        };

        const onRootDragOver = (e, region) => {
            if (dragState.targetPath !== region) {
                dragState.targetPath = region;
            }
        };

        const onRootDrop = (e, region) => {
            const sourceData = e.dataTransfer.getData('sourceInfo');
            let source = null;
            try { source = JSON.parse(sourceData); } catch (e) { }

            if (source) {
                handleMove({
                    action: 'MOVE',
                    source,
                    target: { path: region, position: 'inside' }
                });
            }
            dragState.targetPath = null;
        };

        // --- Core Logic: Handle Move ---
        const handleMove = ({ action, source, target }) => {
            console.log('Action:', action, source, target);

            // 1. Get Source Item (if existing)
            let itemToMove = null;
            if (source.type === 'NEW') {
                itemToMove = createItem(source.component);
            } else {
                itemToMove = TreeHelper.getNodeByPath(structure.value, source.path);
            }

            if (!itemToMove) return;

            // 2. Perform Insertion
            if (source.type === 'EXISTING' && source.path === target.path) return;

            // Use TreeHelper to insert
            TreeHelper.insertNode(structure.value, itemToMove, target.path, target.position);

            // 3. Remove Source (if existing)
            if (source.type === 'EXISTING') {
                const { container: srcArr, index: srcIdx } = TreeHelper.getContainerAndIndex(structure.value, source.path);
                const { container: tgtArr, index: tgtIdx } = TreeHelper.getContainerAndIndex(structure.value, target.path);

                let actualSrcIdx = srcIdx;

                // Fix for same-array shift
                if (srcArr === tgtArr) {
                    if (target.position !== 'inside') {
                        const srcParentPath = source.path.split('.').slice(0, -1).join('.');
                        const tgtParentPath = target.path.split('.').slice(0, -1).join('.');

                        if (srcParentPath === tgtParentPath) {
                            const insertionIdx = target.position === 'after' ? tgtIdx + 1 : tgtIdx;
                            if (insertionIdx <= actualSrcIdx) {
                                actualSrcIdx++;
                            }
                        }
                    }
                }

                if (srcArr && srcArr[actualSrcIdx]) {
                    srcArr.splice(actualSrcIdx, 1);
                }
            }
        };

        // --- Helpers ---
        const createItem = (type) => {
            const item = { type, children: [] };
            if (type === 'columns') {
                item.children = [{ type: 'column', children: [] }, { type: 'column', children: [] }];
            }
            if (['h1', 'h2', 'h3'].includes(type)) item.content = 'Heading';
            if (type === 'p') item.content = 'Lorem ipsum paragraph.';
            return item;
        };

        const selectItem = (path) => {
            selectedPath.value = path;
        };

        const removeSelected = () => {
            if (selectedPath.value) {
                TreeHelper.removeNodeAt(structure.value, selectedPath.value);
                selectedPath.value = null;
            }
        };

        // --- Properties Logic ---
        const selectedItem = computed(() => {
            if (!selectedPath.value) return null;
            return TreeHelper.getNodeByPath(structure.value, selectedPath.value);
        });

        const updateColumnCount = (newCount) => {
            if (!selectedItem.value || selectedItem.value.type !== 'columns') return;
            const current = selectedItem.value.children.length;
            const target = parseInt(newCount);

            if (target > current) {
                for (let i = 0; i < target - current; i++) {
                    selectedItem.value.children.push({ type: 'column', children: [] });
                }
            } else if (target < current) {
                // Remove from end
                selectedItem.value.children.splice(target);
            }
        };

        // Provide
        provide('emitAction', handleMove);
        provide('selectItem', selectItem);
        provide('selectedPath', selectedPath);

        return {
            structure,
            onToolDragStart,
            onRootDragOver,
            onRootDrop,
            dragState,
            selectItem,
            selectedPath,
            selectedItem,
            removeSelected,
            updateColumnCount
        };
    }
};
