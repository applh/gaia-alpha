import { defineComponent, h, ref } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    name: 'TreeView',
    components: { LucideIcon: Icon },
    props: {
        items: { type: Array, required: true },
        // Key names
        idKey: { type: String, default: 'id' },
        childrenKey: { type: String, default: 'children' },
        labelKey: { type: String, default: 'name' },
        // State
        selectedId: { type: [String, Number], default: null },
        expandedIds: { type: Array, default: () => [] }, // Or handle internally if preferred
        // Config
        draggable: { type: Boolean, default: false },
        allowDrop: { type: Function, default: () => true }
    },
    emits: ['select', 'toggle', 'move', 'contextmenu', 'update:expandedIds', 'drop'],
    template: `
    <ul class="tree-list">
        <li v-for="item in items" :key="item[idKey]" 
            class="tree-item-wrapper"
            :class="{ 
                selected: item[idKey] === selectedId,
                'has-children': item[childrenKey] && item[childrenKey].length > 0,
                'is-expanded': isExpanded(item),
                'drag-over-top': dragOverId === item[idKey] && dragPlacement === 'before',
                'drag-over-bottom': dragOverId === item[idKey] && dragPlacement === 'after',
                'drag-over-inside': dragOverId === item[idKey] && dragPlacement === 'inside'
            }"
            @click.stop="$emit('select', item)"
            @contextmenu.prevent="$emit('contextmenu', $event, item)"
            :draggable="draggable"
            @dragstart="onDragStart($event, item)"
            @dragend="onDragEnd($event, item)"
            @dragover.prevent="onDragOver($event, item)"
            @dragleave.prevent="onDragLeave($event, item)"
            @drop.stop="onDrop($event, item)"
            @dragenter.prevent
        >
            <div class="tree-item-content">
                <!-- Toggle Button -->
                <div v-if="hasChildren(item) || item.hasChildren" 
                     class="tree-toggle" 
                     @click.stop="toggle(item)">
                     <slot name="toggle-icon" :expanded="isExpanded(item)">
                        <LucideIcon :name="isExpanded(item) ? 'chevron-down' : 'chevron-right'" size="14" />
                     </slot>
                </div>
                <div v-else class="tree-toggle-spacer"></div>
                
                <!-- Main Content Slot -->
                <div class="tree-node-body">
                    <slot name="item" :item="item" :expanded="isExpanded(item)">
                        {{ item[labelKey] }}
                    </slot>
                </div>
            </div>
            
            <!-- Recursive Children -->
            <transition name="slide-fade">
                <div v-if="isExpanded(item) && hasChildren(item)" class="tree-children">
                     <TreeView 
                        :items="item[childrenKey]"
                        :idKey="idKey"
                        :childrenKey="childrenKey"
                        :labelKey="labelKey"
                        :selectedId="selectedId"
                        :expandedIds="expandedIds"
                        :draggable="draggable"
                        :allowDrop="allowDrop"
                        @select="$emit('select', $event)"
                        @toggle="onChildToggle"
                        @move="onChildMove"
                        @drop="onChildDrop"
                        @contextmenu="$emit('contextmenu', $event)"
                        @update:expandedIds="$emit('update:expandedIds', $event)"
                     >
                        <!-- Pass down slots -->
                        <template #item="{ item, expanded }">
                            <slot name="item" :item="item" :expanded="expanded"></slot>
                        </template>
                        <template #toggle-icon="{ expanded }">
                            <slot name="toggle-icon" :expanded="expanded"></slot>
                        </template>
                     </TreeView>
                </div>
            </transition>
        </li>
    </ul>
    `,
    setup(props, { emit }) {
        const dragPlacement = ref(null); // 'before', 'after', 'inside'
        const dragOverId = ref(null);

        const isExpanded = (item) => {
            return props.expandedIds.includes(item[props.idKey]) || item.expanded;
        };

        const hasChildren = (item) => {
            return item[props.childrenKey] && Array.isArray(item[props.childrenKey]);
        };

        const toggle = (item) => {
            emit('toggle', item);
        };

        const onChildToggle = (item) => {
            emit('toggle', item);
        };

        const onChildMove = (e) => {
            emit('move', e);
        };

        const onChildDrop = (e) => {
            emit('drop', e);
        };

        const onDragStart = (e, item) => {
            if (!props.draggable) return;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('tree-node-id', item[props.idKey]);
            e.target.classList.add('dragging');
        };

        const onDragEnd = (e) => {
            if (!props.draggable) return;
            e.target.classList.remove('dragging');
            dragPlacement.value = null;
            dragOverId.value = null;
        };

        const onDragOver = (e, target) => {
            if (!props.draggable) return;

            // Calculate placement
            const rect = e.currentTarget.getBoundingClientRect();
            const y = e.clientY - rect.top;
            const h = rect.height;

            // Zones: Top 25% = before, Bottom 25% = after, Middle = inside
            if (y < h * 0.25) dragPlacement.value = 'before';
            else if (y > h * 0.75) dragPlacement.value = 'after';
            else dragPlacement.value = 'inside';

            dragOverId.value = target[props.idKey];
        };

        const onDragLeave = () => {
            // dragPlacement.value = null; // Flicker issue if we clear immediately
            // rely on dragOverId mismatch to hide classes
        };

        const onDrop = (e, target) => {
            if (!props.draggable) return;

            const sourceId = e.dataTransfer.getData('tree-node-id');
            // Prevent dropping on self
            if (sourceId && sourceId != target[props.idKey]) {
                // Check if dropping parent into child (cycle detection) - left to parent logic or allowDrop
                if (props.allowDrop(sourceId, target, dragPlacement.value)) {
                    emit('move', { sourceId, target, placement: dragPlacement.value });
                }
            } else if (!sourceId) {
                // External drop logic
                emit('drop', { event: e, target, placement: dragPlacement.value });
            }
            dragPlacement.value = null;
            dragOverId.value = null;
        };

        return {
            isExpanded, hasChildren, toggle, onChildToggle, onChildMove, onChildDrop,
            onDragStart, onDragOver, onDragEnd, onDragLeave, onDrop,
            dragPlacement, dragOverId
        };
    }
}
