
export default {
    name: 'SortTh',
    props: {
        name: { type: String, required: true },
        label: { type: String, required: false },
        currentSort: { type: String, required: true },
        sortDir: { type: String, required: true },
        width: String
    },
    emits: ['sort'],
    template: `
        <th 
            @click="$emit('sort', name)" 
            class="sortable-header" 
            :style="{ width: width }"
        >
            <slot>{{ label }}</slot>
            <span v-if="currentSort === name" class="sort-indicator">
                {{ sortDir === 'asc' ? '▲' : '▼' }}
            </span>
        </th>
    `
};
