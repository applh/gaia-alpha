import { h, ref } from 'vue';

export default {
    name: 'ComponentTree',
    props: {
        layout: Object,
        selectedId: String
    },
    emits: ['select', 'move', 'add'],
    setup(props, { emit }) {

        const dragOverId = ref(null);
        const dragPlacement = ref(null); // 'before', 'after', 'inside'

        const onDragStart = (e, node) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('node-id', node.id);
            e.stopPropagation();
        };

        const onDragOver = (e, node) => {
            e.preventDefault();
            e.stopPropagation();

            const rect = e.currentTarget.getBoundingClientRect();
            const y = e.clientY - rect.top;
            const h = rect.height;

            // Logic: Top 25% = before, Bottom 25% = after, Middle 50% = inside
            // Only allow nesting if it's a container
            const isContainer = ['container', 'row', 'col', 'form'].includes(node.type);

            if (y < h * 0.25) {
                dragPlacement.value = 'before';
            } else if (y > h * 0.75) {
                dragPlacement.value = 'after';
            } else {
                dragPlacement.value = isContainer ? 'inside' : 'after'; // Fallback to after if not container
            }

            dragOverId.value = node.id;
        };

        const onDragLeave = (e) => {
            dragOverId.value = null;
            dragPlacement.value = null;
        };

        const onDrop = (e, targetNode) => {
            e.preventDefault();
            e.stopPropagation();

            const sourceId = e.dataTransfer.getData('node-id');
            const type = e.dataTransfer.getData('component-type');
            const placement = dragPlacement.value;

            dragOverId.value = null;
            dragPlacement.value = null;

            if (sourceId && sourceId !== targetNode.id) {
                emit('move', { sourceId, targetId: targetNode.id, placement });
            } else if (type) {
                emit('add', { type, targetId: targetNode.id, placement });
            }
        };

        const renderTreeNode = (node, depth = 0) => {
            const isSelected = props.selectedId === node.id;
            const isDragOver = dragOverId.value === node.id;
            const placement = dragPlacement.value;

            const liProps = {
                class: {
                    'tree-node': true,
                    'selected': isSelected,
                    'drag-over-top': isDragOver && placement === 'before',
                    'drag-over-bottom': isDragOver && placement === 'after',
                    'drag-over-inside': isDragOver && placement === 'inside'
                },
                style: { paddingLeft: `${depth * 15 + 10}px` },
                draggable: true,
                onClick: (e) => {
                    e.stopPropagation();
                    emit('select', node.id);
                },
                onDragstart: (e) => onDragStart(e, node),
                onDragover: (e) => onDragOver(e, node),
                onDragleave: (e) => onDragLeave(e),
                onDrop: (e) => onDrop(e, node)
            };

            const li = h('li', liProps, [
                h('span', { class: `node-icon icon-${getIcon(node.type)}` }),
                h('span', { class: 'node-label' }, node.label || node.type)
            ]);

            const children = node.children || [];
            if (children.length === 0) return [li];

            // Flatten the result of the map
            const childrenNodes = children.flatMap(child => renderTreeNode(child, depth + 1));
            const ul = h('ul', { class: 'tree-children' }, childrenNodes);

            return [li, ul];
        };

        const getIcon = (type) => {
            const map = {
                'container': 'box',
                'row': 'columns',
                'col': 'pause',
                'stat-card': 'info',
                'data-table': 'table',
                'chart-bar': 'bar-chart',
                'button': 'mouse-pointer',
                'form': 'check-square',
                'action-button': 'zap',
                'link-button': 'link'
            };
            return map[type] || 'circle';
        };

        return () => h('div', { class: 'component-tree' }, [
            h('h4', 'Layers'),
            h('ul', { class: 'tree-root' }, renderTreeNode(props.layout))
        ]);
    },
    styles: `
        .component-tree {
            padding: 10px 0;
            color: #ccc;
        }
        .component-tree h4 {
            padding: 0 15px 10px;
            margin: 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .tree-root, .tree-children {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .tree-node {
            padding: 8px 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            font-size: 0.9em;
            transition: background 0.1s;
            position: relative;
        }
        .tree-node:hover {
            background: rgba(255,255,255,0.05);
        }
        .tree-node.selected {
            background: rgba(99, 102, 241, 0.2);
            border-left: 2px solid #6366f1;
            color: #fff;
        }
        
        /* Drag Indicators */
        .tree-node.drag-over-inside {
            background: rgba(99, 102, 241, 0.2);
            border: 1px dashed #6366f1;
        }
        .tree-node.drag-over-top {
            border-top: 2px solid #6366f1;
        }
        .tree-node.drag-over-bottom {
            border-bottom: 2px solid #6366f1;
        }
        
        .node-icon {
            margin-right: 8px;
            opacity: 0.7;
        }
    `
};
