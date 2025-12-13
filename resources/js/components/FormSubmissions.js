
import { ref, onMounted } from 'vue';

export default {
    props: ['formId'],
    emits: ['close'],
    template: `
        <div class="admin-page">
             <div class="admin-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button @click="$emit('close')">← Back</button>
                    <h2 class="page-title">Submissions</h2>
                </div>
                <button @click="fetchSubmissions">Refresh</button>
            </div>

            <div class="admin-card">
                <div v-if="loading">Loading...</div>
                <div v-else-if="submissions.length === 0">No submissions yet.</div>
                <div v-else style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th v-for="key in keys" :key="key">{{ key }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sub in submissions" :key="sub.id">
                                <td>{{ new Date(sub.submitted_at).toLocaleString() }}</td>
                                <td v-for="key in keys" :key="key">
                                    {{ formatValue(sub.data[key]) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `,
    setup(props) {
        const submissions = ref([]);
        const loading = ref(true);
        const keys = ref([]);

        const fetchSubmissions = async () => {
            loading.value = true;
            try {
                const res = await fetch(`/@/forms/${props.formId}/submissions`);
                if (res.ok) {
                    submissions.value = await res.json();

                    // Extract all unique keys from data objects
                    const uniqueKeys = new Set();
                    submissions.value.forEach(sub => {
                        if (sub.data) {
                            Object.keys(sub.data).forEach(k => uniqueKeys.add(k));
                        }
                    });
                    keys.value = Array.from(uniqueKeys);
                }
            } catch (e) {
                alert('Failed to load submissions');
            } finally {
                loading.value = false;
            }
        };

        const formatValue = (val) => {
            if (typeof val === 'boolean') return val ? 'Yes' : 'No';
            if (val === null || val === undefined) return '-';
            return val;
        };

        onMounted(fetchSubmissions);

        return { submissions, loading, keys, fetchSubmissions, formatValue };
    }
};
