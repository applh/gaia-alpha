export default {
    name: 'DataTable',
    props: {
        columns: Array,
        data: Array,
        loading: Boolean
    },
    template: `
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th v-for="col in columns" :key="col.key">{{ col.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td :colspan="columns.length">Loading...</td>
                    </tr>
                    <tr v-else-if="!data || data.length === 0">
                        <td :colspan="columns.length">No data</td>
                    </tr>
                    <tr v-else v-for="(row, index) in data" :key="index">
                        <td v-for="col in columns" :key="col.key">
                            {{ row[col.key] }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    `
};
