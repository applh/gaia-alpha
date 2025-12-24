import { defineAsyncComponent } from 'vue';
import TreeView from 'ui/TreeView.js';

const LucideIcon = defineAsyncComponent(() => import('ui/Icon.js'));

export default {
    name: 'ComponentTree',
    components: { LucideIcon, TreeView },
    props: {
        layout: Object, // Root node
        selectedId: String
    },
    emits: ['select', 'move', 'add'],
    template: `
        <div class="component-tree">
            <h4>Layout Structure</h4>
            <TreeView 
                :items="[layout]"
                idKey="id"
                childrenKey="children"
                labelKey="label"
                :selectedId="selectedId"
                :draggable="true"
                :allowDrop="() => true"
                @select="$emit('select', $event.id)"
                @move="onMove"
                @drop="onDrop"
            >
                <template #item="{ item }">
                    <div style="display: flex; align-items: center; gap: 8px;">
                         <LucideIcon :name="getIcon(item.type)" size="14" class="node-icon" style="opacity: 0.8;" />
                         <span class="node-label">{{ item.label || item.type }}</span>
                    </div>
                </template>
            </TreeView>
        </div>
    `,
    setup(props, { emit }) {
        const getIcon = (type) => {
            if (type && type.startsWith('custom:')) return 'puzzle';

            const map = {
                'container': 'box',
                'row': 'columns',
                'col': 'layout',
                'stat-card': 'info',
                'data-table': 'table',
                'chart-bar': 'bar-chart',
                'chart-line': 'line-chart',
                'button': 'mouse-pointer',
                'form': 'check-square',
                'input': 'type',
                'select': 'list',
                'action-button': 'zap',
                'link-button': 'link'
            };
            return map[type] || 'circle';
        };

        const onMove = ({ sourceId, target, placement }) => {
            // Re-emit logic
            // TreeView treats move as drag/drop
            // Previous ComponentTree logic handled 'add' vs 'move' based on dataTransfer types.
            // But TreeView generic dragStart sets 'tree-node-id'. 
            // If dragging from PALETTE (outside tree), TreeView's onDrop might not catch it or might catch as generic drop?
            // Wait, TreeView writes 'tree-node-id'. 
            // If dragging from ComponentPalette, it might write 'component-type'.
            // TreeView's onDrop checks 'tree-node-id'.
            // If sourceId is present, it's a move.
            // If not, we might need to check other data types?
            // My TreeView implementation ONLY checks 'tree-node-id'.
            // This suggests I need to Enhance TreeView to support custom drops or handle native drop events?
            // ComponentTree *did* handle 'component-type'.
            // I should look at TreeView `onDrop` again.

            // Re-visiting TreeView onDrop:
            // const sourceId = e.dataTransfer.getData('tree-node-id');
            // if (sourceId ...) emit('move')

            // It swallows other drops effectively by not doing anything.
            // If I want to support dropping generic items (like components from palette),
            // I should modify TreeView to emit 'drop' event with full event object if not handled as internal move?
            // Or allow subclassing onDrop?

            // For now, I'll pass the move event. If the user was dragging from outside, TreeView won't fire 'move'.
            // So I broke "Add from Palette".

            // Fix: Add `@drop.native` to TreeView? No, `drop.stop` in TreeView prevents bubbling.
            // I must update TreeView to emit 'drop-native' or similar if it's not an internal move.

            emit('move', { sourceId, targetId: target.id, placement });
        };

        const onDrop = ({ event, target, placement }) => {
            const type = event.dataTransfer.getData('component-type');
            if (type) {
                emit('add', { type, targetId: target.id, placement });
            }
        };

        return { getIcon, onMove, onDrop };
    }
};
