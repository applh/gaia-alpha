
import { ref, computed } from 'vue';

export function useSorting(dataRef, defaultCol = 'id', defaultDir = 'asc', customGetters = {}) {
    const sortColumn = ref(defaultCol);
    const sortDirection = ref(defaultDir);

    const sortBy = (col) => {
        if (sortColumn.value === col) {
            sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortColumn.value = col;
            sortDirection.value = 'asc';
        }
    };

    const sortedData = computed(() => {
        if (!dataRef.value) return [];
        if (!sortColumn.value) return dataRef.value;

        return [...dataRef.value].sort((a, b) => {
            let valA, valB;

            // Use custom getter if available
            if (customGetters[sortColumn.value]) {
                valA = customGetters[sortColumn.value](a);
                valB = customGetters[sortColumn.value](b);
            } else {
                valA = a[sortColumn.value];
                valB = b[sortColumn.value];
            }

            // Date detection (naive check or by column name convention)
            // Or rely on user to pass custom getter for dates if they are strings?
            // "created_at" is usually string, but we want date comparison.
            if (sortColumn.value.endsWith('_at') || sortColumn.value.endsWith('Date')) {
                valA = new Date(valA || 0).getTime();
                valB = new Date(valB || 0).getTime();
            }

            if (valA == null) return 1;
            if (valB == null) return -1;

            if (typeof valA === 'string') valA = valA.toLowerCase();
            if (typeof valB === 'string') valB = valB.toLowerCase();

            if (valA < valB) return sortDirection.value === 'asc' ? -1 : 1;
            if (valA > valB) return sortDirection.value === 'asc' ? 1 : -1;
            return 0;
        });
    });

    return { sortColumn, sortDirection, sortBy, sortedData };
}
