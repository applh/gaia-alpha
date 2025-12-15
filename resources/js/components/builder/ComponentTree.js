import { h, ref, reactive, defineAsyncComponent } from 'vue';

const LucideIcon = defineAsyncComponent(() => import('../Icon.js'));

export default {
    name: 'ComponentTree',
    props: {
        layout: Object,
        selectedId: String
    },
    emits: ['select', 'move', 'add'],
    setup(props, { emit }) {
        const collapsed = reactive(new Set());
        const dragOverId = ref(null);
        const dragPlacement = ref(null); // 'before', 'after', 'inside'

        const toggle = (e, id) => {
            e.stopPropagation(); // Prevent select
            if (collapsed.has(id)) collapsed.delete(id);
            else collapsed.add(id);
        };

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
            const hasChildren = node.children && node.children.length > 0;
            const isCollapsed = collapsed.has(node.id);

            const liProps = {
                class: {
                    'tree-node': true,
                    'selected': isSelected,
                    'drag-over-top': isDragOver && placement === 'before',
                    'drag-over-bottom': isDragOver && placement === 'after',
                    'drag-over-inside': isDragOver && placement === 'inside'
                },
                style: { paddingLeft: `${depth * 15 + 5}px` },
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

            const content = [
                // Toggle Button
                h('span', {
                    class: 'tree-toggle',
                    style: {
                        display: 'inline-flex',
                        width: '20px',
                        justifyContent: 'center',
                        cursor: 'pointer',
                        marginRight: '2px',
                        visibility: hasChildren ? 'visible' : 'hidden'
                    },
                    onClick: (e) => hasChildren && toggle(e, node.id)
                }, [
                    h(LucideIcon, {
                        name: isCollapsed ? 'chevron-right' : 'chevron-down',
                        size: 14,
                        color: '#9ca3af'
                    })
                ]),

                // Node Icon
                h(LucideIcon, {
                    name: getIcon(node.type),
                    size: 14,
                    class: 'node-icon',
                    style: { marginRight: '8px', opacity: 0.8 }
                }),

                // Label (Wrap in span for alignment)
                h('span', { class: 'node-label' }, node.label || node.type)
            ];

            const li = h('li', liProps, content);

            // Conditional children rendering
            if (!hasChildren || isCollapsed) return [li];

            // Use flatMap to flatten the array of arrays returned by recursion
            const childrenNodes = node.children.flatMap(child => renderTreeNode(child, depth + 1));
            const ul = h('ul', { class: 'tree-children' }, childrenNodes);

            return [li, ul];
        };

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

        return () => h('div', { class: 'component-tree' }, [
            h('h4', 'Layout Structure'),
            h('ul', { class: 'tree-root' }, renderTreeNode(props.layout))
        ]);
    },
    styles: `
        .component-tree {
            padding: 10px 0;
            color: #d1d5db;
        }
        .component-tree h4 {
            padding: 0 15px 10px;
            margin: 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
        }
        .tree-root, .tree-children {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .tree-node {
            padding: 6px 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            font-size: 0.9em;
            transition: all 0.1s;
            position: relative;
            line-height: 1.2;
        }
        .tree-node:hover {
            background: rgba(255,255,255,0.05);
        }
        .tree-node.selected {
            background: rgba(99, 102, 241, 0.15);
            color: #fff;
        }
        .tree-node.selected::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #6366f1;
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
        
        .node-label {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    `
};
