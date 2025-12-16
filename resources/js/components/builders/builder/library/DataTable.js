import { ref, onMounted, watch, computed } from 'vue';

export default {
    name: 'DataTable',
    props: {
        columns: Array,
        data: Array,
        loading: Boolean,
        endpoint: String // URL to fetch data from
    },
    setup(props) {
        const localData = ref([]);
        const localLoading = ref(false);
        const error = ref(null);

        const fetchData = async () => {
            if (!props.endpoint) return;

            localLoading.value = true;
            try {
                const res = await fetch(props.endpoint);
                if (!res.ok) throw new Error('Failed to fetch data');
                const result = await res.json();

                // Determine if result is array or paginated object
                if (Array.isArray(result)) {
                    localData.value = result;
                } else if (result.data && Array.isArray(result.data)) {
                    localData.value = result.data;
                } else {
                    localData.value = [];
                }

            } catch (e) {
                console.error("DataTable fetch error:", e);
                error.value = e.message;
            } finally {
                localLoading.value = false;
            }
        };

        onMounted(() => {
            if (props.endpoint) {
                fetchData();
            }
        });

        // Watch for endpoint changes
        watch(() => props.endpoint, (newVal) => {
            if (newVal) fetchData();
        });

        const displayData = computed(() => {
            if (props.endpoint) return localData.value;
            return props.data || [];
        });

        const autoColumns = computed(() => {
            if (props.columns && props.columns.length > 0) return props.columns;
            if (displayData.value && displayData.value.length > 0) {
                const firstRow = displayData.value[0];
                return Object.keys(firstRow).map(key => ({
                    key: key,
                    label: key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ')
                }));
            }
            return [];
        });

        const isLoading = computed(() => {
            return props.loading || localLoading.value;
        });

        return { localData, localLoading, error, displayData, isLoading, autoColumns };
    },
    template: `
        <div class="data-table-wrapper">
             <div v-if="error" class="error-message">Error: {{ error }}</div>
            <table class="data-table">
                <thead>
                    <tr>
                         <th v-if="autoColumns.length === 0">Data</th> <!-- Fallback header -->
                        <th v-for="col in autoColumns" :key="col.key">{{ col.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading">
                        <td :colspan="autoColumns.length > 0 ? autoColumns.length : 1">Loading...</td>
                    </tr>
                    <tr v-else-if="!displayData || displayData.length === 0">
                        <td :colspan="autoColumns.length > 0 ? autoColumns.length : 1">No data</td>
                    </tr>
                    <tr v-else v-for="(row, index) in displayData" :key="index">
                        
                         <!-- Auto-generate columns if not provided -->
                         <td v-if="autoColumns.length === 0">
                             {{ row }}
                         </td>

                        <td v-else v-for="col in autoColumns" :key="col.key">
                            {{ row[col.key] }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    `
};
