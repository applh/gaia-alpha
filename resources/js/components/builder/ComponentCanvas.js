import { h, computed } from 'vue';

export default {
    name: 'ComponentCanvas',
    props: {
        layout: Object,
        selectedId: String
    },
    emits: ['select', 'update'],
    setup(props, { emit }) {

        // Recursive render function
        const renderNode = (node) => {
            const isSelected = props.selectedId === node.id;

            // Determine class based on node type
            let classes = ['canvas-node', `node-${node.type}`];
            if (isSelected) classes.push('selected');

            // Events
            const onClick = (e) => {
                e.stopPropagation();
                emit('select', node.id);
            };

            // Children
            const children = (node.children || []).map(child => renderNode(child));

            return h('div', {
                class: classes.join(' '),
                onClick
            }, [
                h('div', { class: 'node-label' }, node.label || node.type),
                h('div', { class: 'node-content' }, children)
            ]);
        };

        return () => h('div', { class: 'component-canvas' }, [
            renderNode(props.layout)
        ]);
    }
};
