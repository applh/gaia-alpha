import { computed, inject } from 'vue';
import { useDragDrop } from 'composables/useDragDrop.js';

const TreeItem = {
    name: 'TreeItem',
    props: ['element', 'path', 'index'],
    setup(props) {
        const emit = inject('emitAction');
        const selectItem = inject('selectItem');
        const selectedPath = inject('selectedPath');

        // Reuse drag drop logic
        // We need to pass our specific emit callback
        const { activeDrag, dragState, onDragStart: startDrag, onDragOver: handleDragOver, onDrop: handleDrop } = useDragDrop();

        const isContainer = computed(() => {
            return ['header', 'main', 'footer', 'section', 'columns', 'column', 'div'].includes(props.element.type);
        });

        const isSelected = computed(() => selectedPath.value === props.path);

        const onDragStart = (e) => {
            startDrag(e, props.element, props.path, 'EXISTING');
        };

        const onDragOver = (e) => {
            handleDragOver(e, props.path, isContainer.value);
        };

        const onDrop = (e) => {
            handleDrop(e, props.path, (payload) => {
                emit({
                    action: 'MOVE',
                    source: payload.source,
                    target: payload.target
                });
            });
        };

        const isDropTarget = computed(() => dragState.targetPath === props.path);

        return {
            isContainer,
            isSelected,
            onDragStart,
            onDragOver,
            onDrop,
            isDropTarget,
            dragPosition: computed(() => dragState.position),
            selectItem,
            dragState // Expose for template class bindings
        };
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
                   <!-- Preview text could go here -->
                </span>
            </div>

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

// Handle recursion
TreeItem.components = { TreeItem };

export default TreeItem;
