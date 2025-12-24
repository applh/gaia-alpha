export default {
    props: {
        data: {
            type: Array,
            default: () => []
        },
        columns: {
            type: Array,
            default: () => []
        },
        pagination: {
            type: Object,
            default: null // { total, pageSize, currentPage }
        }
    },
    emits: ['page-change', 'sort-change'],
    template: `
        <div class="ui-data-table-container">
            <table class="ui-data-table">
                <thead>
                    <tr>
                        <th 
                            v-for="col in columns" 
                            :key="col.prop || col.label"
                            :style="{ width: col.width, textAlign: col.align || 'left' }"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, rowIndex) in data" :key="rowIndex">
                        <td 
                            v-for="col in columns" 
                            :key="col.prop || col.label"
                            :style="{ textAlign: col.align || 'left' }"
                        >
                            <slot 
                                :name="'col-' + col.prop" 
                                :row="row" 
                                :index="rowIndex"
                            >
                                {{ row[col.prop] }}
                            </slot>
                        </td>
                    </tr>
                    <tr v-if="data.length === 0">
                        <td :colspan="columns.length" class="ui-data-table-empty">
                            No data available
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div v-if="pagination" class="ui-data-table-pagination">
                <pagination
                    :total="pagination.total"
                    :page-size="pagination.pageSize"
                    :current-page="pagination.currentPage"
                    @current-change="$emit('page-change', $event)"
                />
            </div>
        </div>
    `
};
