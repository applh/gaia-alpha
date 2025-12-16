import { ref, reactive } from 'vue';

export function useDragDrop(emitMove) {
    const activeDrag = ref(null);
    const dragState = reactive({
        targetPath: null,
        position: null
    });

    const onDragStart = (e, item, path, type = 'EXISTING') => {
        e.stopPropagation();
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('sourceInfo', JSON.stringify({
            path,
            type,
            item: type === 'NEW' ? item : null
        }));
        activeDrag.value = { path, type };
    };

    const calculateDropPosition = (e, isContainer) => {
        const rect = e.currentTarget.getBoundingClientRect();
        const y = e.clientY - rect.top;
        const h = rect.height;
        const relY = y / h;

        let position = 'inside';

        if (isContainer) {
            if (relY < 0.25) position = 'before';
            else if (relY > 0.75) position = 'after';
            else position = 'inside';
        } else {
            if (relY < 0.5) position = 'before';
            else position = 'after';
        }

        return position;
    };

    const onDragOver = (e, path, isContainer) => {
        e.preventDefault();
        e.stopPropagation();

        // Prevent dropping inside itself
        if (activeDrag.value && activeDrag.value.path === path) return;

        const position = calculateDropPosition(e, isContainer);

        dragState.targetPath = path;
        dragState.position = position;
    };

    const onDrop = (e, targetPath, onSuccess) => {
        e.preventDefault();
        e.stopPropagation();

        const sourceData = e.dataTransfer.getData('sourceInfo') || e.dataTransfer.getData('type');
        let source = null;

        try {
            source = JSON.parse(sourceData);
        } catch (err) {
            // If simple string, assume NEW component type from simple toolbox
            if (sourceData && !sourceData.startsWith('{')) {
                source = { type: 'NEW', component: sourceData };
            }
        }

        if (source) {
            onSuccess({
                source,
                target: { path: targetPath, position: dragState.position }
            });
        }

        dragState.targetPath = null;
        dragState.position = null;
    };

    return {
        activeDrag,
        dragState,
        onDragStart,
        onDragOver,
        onDrop
    };
}
