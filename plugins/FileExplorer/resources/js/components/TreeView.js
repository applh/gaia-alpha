import { ref, computed } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    name: 'TreeView',
    components: { LucideIcon: Icon },
    props: {
        items: { type: Array, required: true },
        selectedPath: { type: String, default: '' },
        selectedId: { type: Number, default: 0 }
    },
    emits: ['select', 'move', 'contextmenu'],
    template: `
    <ul class="tree-list">
        <li v-for="item in items" :key="item.path || item.id" 
            :class="['tree-item', { 
                selected: (item.path && item.path === selectedPath) || (item.id && item.id === selectedId),
                'is-dir': item.isDir || item.type === 'folder'
            }]"
            @click.stop="$emit('select', item)"
            @contextmenu.prevent="$emit('contextmenu', $event, item)"
            draggable="true"
            @dragstart="onDragStart($event, item)"
            @dragover.prevent
            @drop.stop="onDrop($event, item)"
        >
            <div class="item-label">
                <LucideIcon :name="getIcon(item)" size="16" />
                <span class="label-text">{{ item.name }}</span>
            </div>
            
            <tree-view v-if="(item.isDir || item.type === 'folder') && item.children && item.children.length"
                :items="item.children"
                :selectedPath="selectedPath"
                :selectedId="selectedId"
                @select="$emit('select', $event)"
                @move="$emit('move', $event)"
                @contextmenu="$emit('contextmenu', $event)"
            />
        </li>
    </ul>
    `,
    setup(props, { emit }) {
        const getIcon = (item) => {
            if (item.isDir || item.type === 'folder') return 'folder';
            const ext = item.ext || (item.name.includes('.') ? item.name.split('.').pop() : '');
            switch (ext.toLowerCase()) {
                case 'php': return 'code-2';
                case 'js': return 'scroll';
                case 'css': return 'palette';
                case 'json': return 'braces';
                case 'md': return 'file-text';
                case 'png':
                case 'jpg':
                case 'jpeg':
                case 'webp': return 'image';
                case 'sql':
                case 'sqlite': return 'database';
                default: return 'file';
            }
        };

        const onDragStart = (e, item) => {
            e.dataTransfer.setData('item', JSON.stringify(item));
        };

        const onDrop = (e, target) => {
            if (!(target.isDir || target.type === 'folder')) return;
            const source = JSON.parse(e.dataTransfer.getData('item'));
            if (source.path === target.path || source.id === target.id) return;
            emit('move', { source, target });
        };

        return { getIcon, onDragStart, onDrop };
    }
};
