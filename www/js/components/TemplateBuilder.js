import { ref, watch, computed, provide, inject, reactive } from 'vue';

// --- Recursive Tree Component ---
const TreeItem = {
    name: 'TreeItem',
    props: ['element', 'path', 'index'],
    setup(props) {
        const emit = inject('emitAction'); // Use inject to communicate with root
        const activeDrag = inject('activeDrag');
        const dragState = inject('dragState');
        const selectItem = inject('selectItem');
        const selectedPath = inject('selectedPath');

        // Check if item is container
        const isContainer = computed(() => {
            return ['header', 'main', 'footer', 'section', 'columns', 'column', 'div'].includes(props.element.type);
        });

        const isSelected = computed(() => selectedPath.value === props.path);

        const onDragStart = (e) => {
            e.stopPropagation();
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('sourceInfo', JSON.stringify({
                path: props.path,
                type: 'EXISTING'
            }));
            activeDrag.value = { path: props.path, type: 'EXISTING' };
        };

        const onDragOver = (e) => {
            e.preventDefault();
            e.stopPropagation();

            const rect = e.currentTarget.getBoundingClientRect();
            // Only calculate if we are hovering THIS element specifically, NOT a child
            // But events bubble. We need strictly checking e.currentTarget vs logic? 
            // Vue .stop modifier on children usually helps, but manual calc is safer.

            const y = e.clientY - rect.top;
            const h = rect.height;
            const relY = y / h;

            let position = 'inside';

            // Logic: 
            // If container: Top 25%->Before, Bottom 25%->After, Middle->Inside
            // If leaf: Top 50%->Before, Bottom 50%->After

            if (isContainer.value) {
                if (relY < 0.25) position = 'before';
                else if (relY > 0.75) position = 'after';
                else position = 'inside';
            } else {
                if (relY < 0.5) position = 'before';
                else position = 'after';
            }

            // Cannot drop inside itself
            if (activeDrag.value && activeDrag.value.path === props.path) return;
            // Cannot drop inside its own children (handled by path check in root)

            dragState.targetPath = props.path;
            dragState.position = position;
        };

        const onDragLeave = (e) => {
            // We rely on the parent/others to take over, or simple timeout clear?
            // Actually, keeping state in root 'dragState' is better reactive source
        };

        const onDrop = (e) => {
            e.preventDefault();
            e.stopPropagation();

            const sourceData = e.dataTransfer.getData('sourceInfo') || e.dataTransfer.getData('type');

            let source = null;
            try {
                source = JSON.parse(sourceData);
            } catch (err) {
                // If not JSON, it might be raw type from toolbox
                if (sourceData && !sourceData.startsWith('{')) {
                    source = { type: 'NEW', component: sourceData };
                }
            }

            if (source) {
                emit({
                    action: 'MOVE',
                    source,
                    target: { path: props.path, position: dragState.position }
                });
            }

            dragState.targetPath = null;
            dragState.position = null;
        };

        // Helper to check if we are currently the drop target
        const isDropTarget = computed(() => dragState.targetPath === props.path);

        return {
            isContainer,
            isSelected,
            onDragStart,
            onDragOver,
            onDrop,
            isDropTarget,
            dragPosition: computed(() => dragState.position),
            selectItem
        };
    },
    components: {
        get TreeItem() { return TreeItem }
    },
    template: `
        <div class="tree-item-wrapper"
             :class="{
                 'selected': isSelected,
                 'drop-before': isDropTarget && dragPosition === 'before',
                 'drop-after': isDropTarget && dragPosition === 'after',
                 'drop-inside': isDropTarget && dragPosition === 'inside'
             }"
             draggable="true"
             @dragstart="onDragStart"
             @dragover="onDragOver"
             @drop="onDrop"
             @click.stop="selectItem(path)"
             style="margin-bottom: 2px;">
            
            <div class="tree-node-content" 
                 :style="{
                     padding: '6px 8px',
                     background: isSelected ? 'var(--accent-color)' : 'rgba(255,255,255,0.05)',
                     border: '1px solid var(--border-color)',
                     borderRadius: '4px',
                     cursor: 'grab',
                     display: 'flex',
                     alignItems: 'center',
                     gap: '8px',
                     color: isSelected ? '#fff' : 'var(--text-primary)'
                 }">
                <span class="node-icon" :style="{ opacity: isSelected ? 1 : 0.7, fontSize: '0.9em' }">
                    {{ isContainer ? '🗂' : '📄' }}
                </span>
                <span class="node-type" :style="{ fontWeight: 600, fontSize: '0.9em', textTransform: 'uppercase', color: isSelected ? '#fff' : 'var(--text-primary)' }">
                    {{ element.type }}
                </span>
                <span v-if="!isContainer" class="node-preview" :style="{ marginLeft: 'auto', fontSize: '0.8em', color: isSelected ? 'rgba(255,255,255,0.8)' : 'var(--text-secondary)' }">
                   <!-- Preview text -->
                </span>
            </div>

            <!-- Children -->
            <div v-if="isContainer && element.children && element.children.length > 0" class="tree-children" style="padding-left: 20px; margin-top: 4px; border-left: 1px dashed var(--border-color);">
                <TreeItem 
                    v-for="(child, idx) in element.children" 
                    :key="idx"
                    :index="idx"
                    :element="child"
                    :path="path + '.children.' + idx"
                />
            </div>
            <div v-if="isContainer && (!element.children || element.children.length === 0)" 
                 class="empty-indicator" 
                 style="font-size: 0.8em; color: var(--text-secondary); padding-left: 20px; padding-top: 4px; font-style: italic;">
                (Empty)
            </div>
        </div>
    `
};

// --- Preview Component ---
const PreviewRenderer = {
    name: 'PreviewRenderer',
    props: ['element'],
    components: {
        get PreviewRenderer() { return PreviewRenderer }
    },
    template: `
        <!-- Layouts -->
        <div v-if="element.type === 'header'" class="preview-block header-block">
            <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
        </div>

        <div v-if="element.type === 'main'" class="preview-block main-block">
            <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
        </div>

        <div v-if="element.type === 'footer'" class="preview-block footer-block">
            <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
        </div>

        <div v-if="element.type === 'section'" class="preview-block section-block" style="padding: 20px; border: 1px dashed #ccc; margin: 10px 0;">
            <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
            <div v-if="!element.children?.length" class="empty-preview">Empty Section</div>
        </div>
        
        <div v-if="element.type === 'columns'" class="preview-block columns-block" style="display: flex; gap: 20px;">
             <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
        </div>

        <div v-if="element.type === 'column'" class="preview-block column-block" style="flex: 1; padding: 10px; border: 1px dotted #ccc;">
             <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
        </div>

        <!-- Content -->
        <h1 v-if="element.type === 'h1'">{{ element.content || 'Heading 1' }}</h1>
        <h2 v-if="element.type === 'h2'">{{ element.content || 'Heading 2' }}</h2>
        <h3 v-if="element.type === 'h3'">{{ element.content || 'Heading 3' }}</h3>
        <p v-if="element.type === 'p'">{{ element.content || 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.' }}</p>
        <div v-if="element.type === 'image'" style="width: 100%; height: 150px; background: #eee; display: flex; align-items: center; justify-content: center; color: #888; overflow: hidden;">
             <img v-if="element.src" :src="element.src" style="width:100%; height:100%; object-fit:cover;" />
             <span v-else>Image Preview</span>
        </div>
    `
};

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
        const activeDrag = ref(null);
        const dragState = reactive({ targetPath: null, position: null });
        const selectedPath = ref(null);

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
                dragState.position = 'inside'; // Root is always 'inside'
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
                // If it's existing, we need to remove it first? 
                // Wait, if we remove first, indices shift. 
                // Easier to make a copy, insert, THEN remove?
                // But we need to handle "same container" reordering separately to avoid index mess.
                itemToMove = getItemAt(source.path);
            }

            if (!itemToMove) return;

            // 2. Perform Insertion
            // If dragging existing item to same place, ignore
            if (source.type === 'EXISTING' && source.path === target.path) return;

            // Insert Logic
            const targetParentPath = getParentPath(target.path);
            const targetIndex = getIndexFromPath(target.path);

            // Special Case: Root drop
            if (['header', 'main', 'footer'].includes(target.path)) {
                structure.value[target.path].push(itemToMove);
            } else {
                if (target.position === 'inside') {
                    // Insert into target's children
                    const targetItem = getItemAt(target.path);
                    if (targetItem) {
                        if (!targetItem.children) targetItem.children = [];
                        targetItem.children.push(itemToMove);
                    }
                } else if (target.position === 'before') {
                    const [arr] = getContainerAndIndex(target.path);
                    if (arr) arr.splice(targetIndex, 0, itemToMove);
                } else if (target.position === 'after') {
                    const [arr] = getContainerAndIndex(target.path);
                    if (arr) arr.splice(targetIndex + 1, 0, itemToMove);
                }
            }

            // 3. Remove Source (if existing)
            // Note: If we inserted BEFORE in same array, source index might have incremented +1
            // If AFTER, source index same (if source < target)
            // This is tricky.
            // Safer approach: Remove FIRST, then Insert? 
            // If we remove first, target path might become invalid (indices shift).
            // Solution: Handle removal carefully.

            if (source.type === 'EXISTING') {
                removeItemAt(source.path, target.path, target.position);
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

        const getItemAt = (path) => {
            const parts = path.split('.');
            let current = structure.value;
            // header -> [0] -> children -> [1]
            // path example: header.0.children.1
            // parts: [header, 0, children, 1]

            // Root check
            if (parts.length === 1) return structure.value[parts[0]];

            try {
                current = structure.value[parts[0]]; // header array
                for (let i = 1; i < parts.length; i++) {
                    const key = parts[i]; // 0 or children
                    // If key is number, array index
                    current = current[key];
                }
                return current;
            } catch (e) { return null; }
        };

        const removeItemAt = (sourcePath, targetPath, targetPos) => {
            // We need to re-find the item because indices might have changed due to insertion.
            // Actually, if we use unique IDs for items we could just find by ID.
            // Since we don't have IDs, this is hard.

            // ALTERNATIVE: Use a "placeholder" or clone during drag?
            // Simplest "Good Enough" Logic for now:
            // If source array === target array:
            //   Calculate real indices.
            // If different array:
            //   Just delete source (original path still valid? No, but close enough if we operate fast).

            // Let's rely on Vue Reactivity:
            // The item object reference `itemToMove` is now likely in TWO places in the tree.
            // We want to remove the instance at `sourcePath`.

            // WARNING: If we inserted it into SAME array, `sourcePath` might need +1 correction.

            const [srcArr, srcIdx] = getContainerAndIndex(sourcePath);
            // Verify if this is indeed the item (same reference)
            // If we inserted before, srcIdx might now point to old item, but shifted? nothing shifted yet at srcIdx position ideally?
            // Wait, if target is BEFORE source in same array, source index IS +1 now.

            let actualSrcIdx = srcIdx;

            // Same container check
            const srcContainerPath = getParentPath(sourcePath);
            const tgtContainerPath = targetPos === 'inside' ? targetPath : getParentPath(targetPath);

            // If insertion happened in same container
            if (srcContainerPath === tgtContainerPath) {
                const tgtIdxRaw = targetPos === 'inside' ? 999 : getIndexFromPath(targetPath);
                // If target was before source
                if (targetPos === 'before' && tgtIdxRaw <= srcIdx) actualSrcIdx++;
                if (targetPos === 'after' && tgtIdxRaw < srcIdx) actualSrcIdx++;
                // Inside doesn't affect source index (different array level)
            }

            if (srcArr && srcArr[actualSrcIdx]) {
                srcArr.splice(actualSrcIdx, 1);
            }
        };

        const getContainerAndIndex = (path) => {
            const parts = path.split('.');
            const idx = parseInt(parts.pop());
            const parentPath = parts.join('.');

            // if path was "header.0", parts=[header], idx=0.
            // parent is "header". getItemAt("header") returns the array.
            const container = getItemAt(parentPath);
            return [container, idx];
        };

        const getParentPath = (path) => {
            const parts = path.split('.');
            parts.pop();
            return parts.join('.');
        };

        const getIndexFromPath = (path) => {
            const parts = path.split('.');
            return parseInt(parts[parts.length - 1]);
        };

        const selectItem = (path) => {
            selectedPath.value = path;
        };

        const removeSelected = () => {
            if (selectedPath.value) {
                const [arr, idx] = getContainerAndIndex(selectedPath.value);
                if (arr) arr.splice(idx, 1);
                selectedPath.value = null;
            }
        };

        // --- Properties Logic ---
        const selectedItem = computed(() => {
            if (!selectedPath.value) return null;
            return getItemAt(selectedPath.value);
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
        provide('activeDrag', activeDrag);
        provide('dragState', dragState);
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
