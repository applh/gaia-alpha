import { h, computed } from 'vue';

export default {
    name: 'ComponentCanvas',
    props: {
        layout: Object,
        selectedId: String
    },
    emits: ['select', 'update'],
    setup(props, { emit }) {

        const onDragOver = (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            e.currentTarget.classList.add('drag-over');
        };

        const onDragLeave = (e) => {
            e.currentTarget.classList.remove('drag-over');
        };

        const onDrop = (e, targetNode) => {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('drag-over');

            const type = e.dataTransfer.getData('component-type');
            if (type) {
                addComponent(targetNode, type);
            }
        };

        const addComponent = (targetNode, type) => {
            const newComponent = {
                id: 'comp_' + Math.random().toString(36).substr(2, 9),
                type: type,
                label: `New ${type}`,
                props: {},
                children: []
            };

            // If target has children array, add to it. Otherwise add to parent (not handled here fully properly for leaf nodes, simple logic for now)
            if (!targetNode.children) targetNode.children = [];
            targetNode.children.push(newComponent);

            // Emit update to trigger reactivity in parent
            emit('select', newComponent.id);
        };

        // Recursive render function
        const renderNode = (node) => {
            const isSelected = props.selectedId === node.id;
            const isContainer = node.type === 'container' || node.type === 'row' || node.type === 'col';

            // Determine class based on node type
            let classes = ['canvas-node', `node-${node.type}`];
            if (isSelected) classes.push('selected');
            if (isContainer) classes.push('is-container');

            // Events
            const onClick = (e) => {
                e.stopPropagation();
                emit('select', node.id);
            };

            const nodeProps = {
                class: classes.join(' '),
                onClick
            };

            // Drag drop handlers for containers
            if (isContainer || node.id === props.layout.id) { // Allow root drop
                nodeProps.onDragover = onDragOver;
                nodeProps.onDragleave = onDragLeave;
                nodeProps.onDrop = (e) => onDrop(e, node);
            }

            // Children
            const children = (node.children || []).map(child => renderNode(child));

            // Empty state for containers
            if (isContainer && children.length === 0) {
                children.push(h('div', { class: 'empty-placeholder' }, 'Drop components here'));
            }

            // Markdown Preview
            if (node.type === 'markdown') {
                children.push(h('div', { class: 'markdown-preview' }, [
                    h('div', { class: 'markdown-label' }, 'Markdown Content:'),
                    h('pre', { class: 'markdown-content' }, node.props.content || '(Empty Markdown)')
                ]));
            }

            return h('div', nodeProps, [
                h('div', { class: 'node-label' }, node.label || node.type),
                h('div', { class: 'node-content' }, children)
            ]);
        };

        return () => h('div', { class: 'component-canvas' }, [
            renderNode(props.layout)
        ]);
    },
    styles: `
        .component-canvas {
            min-height: 100%;
            width: 100%;
            padding: 20px;
        }
        .canvas-node {
            border: 1px dashed #4a5568;
            margin: 5px;
            padding: 10px;
            background: rgba(255,255,255,0.02);
            position: relative;
            min-height: 50px;
        }
        .canvas-node.selected {
            border: 1px solid #6366f1;
            background: rgba(99, 102, 241, 0.1);
            box-shadow: 0 0 0 1px #6366f1;
        }
        .canvas-node.drag-over {
            background: rgba(72, 187, 120, 0.1);
            border-color: #48bb78;
        }
        .node-label {
            font-size: 0.75rem;
            color: #a0aec0;
            margin-bottom: 5px;
            pointer-events: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .node-content {
            min-height: 20px;
        }
        .empty-placeholder {
            text-align: center;
            padding: 20px;
            color: #718096;
            font-style: italic;
            font-size: 0.8rem;
            border: 1px dashed #2d3748;
            border-radius: 4px;
        }
        
        /* Specific Layout Styling for Canvas Preview */
        .node-row > .node-content {
            display: flex;
            flex-wrap: wrap;
            margin: -5px;
        }
        .node-col {
            flex: 1; /* Default flex behavior for canvas */
        }
        .markdown-preview {
            padding: 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 4px;
        }
        .markdown-label {
            font-size: 0.7rem;
            color: #718096;
            margin-bottom: 5px;
        }
        .markdown-content {
            font-family: monospace;
            white-space: pre-wrap;
            color: #e2e8f0;
            font-size: 0.85rem;
            margin: 0;
        }
    `
};
