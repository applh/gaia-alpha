
import { ref, onMounted, computed } from 'vue';
// Assuming Chart is available globally or as a vendor module.
// If it's an ES module in vendor/chart.js, we can try importing it, but dynamic imports are safer if we are not sure of the build system.
// For now, I'll rely on simple CSS bars to be 100% sure it works without build issues, as Chart.js integration might vary.
// Actually, simple CSS bars are often better for this kind of simple stats.

export default {
    props: ['formId'],
    emits: ['close'],
    template: `
        <div class="admin-page form-dashboard">
            <div class="admin-header">
                 <div style="display: flex; align-items: center; gap: 10px;">
                    <button @click="$emit('close')">← Back</button>
                    <h2 class="page-title">Stats: {{ form?.title }}</h2>
                </div>
            </div>

            <div v-if="loading" class="loading-state">Loading stats...</div>
            <div v-else-if="error" class="error-state">{{ error }}</div>
            
            <div v-else class="dashboard-content">
                <!-- Overview Cards -->
                <div class="stats-overview">
                    <div class="stat-card">
                        <h3>Total Submissions</h3>
                        <div class="stat-value">{{ stats.total }}</div>
                    </div>
                    <div v-if="stats.avgScore !== null" class="stat-card">
                        <h3>Average Score</h3>
                        <div class="stat-value">{{ parseFloat(stats.avgScore).toFixed(1) }}</div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-container">
                    <div v-for="(dist, key) in stats.distribution" :key="key" class="chart-card admin-card">
                        <h4>{{ dist.label }}</h4>
                        <div class="bar-chart">
                            <div v-for="(count, label) in dist.counts" :key="label" class="bar-row">
                                <div class="bar-label">{{ label }}</div>
                                <div class="bar-track">
                                    <div class="bar-fill" :style="{ width: getPercent(count, stats.total) + '%' }"></div>
                                    <span class="bar-count">{{ count }} ({{ getPercent(count, stats.total) }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                 <div v-if="Object.keys(stats.distribution).length === 0" class="empty-state">
                    <p>No categorical data to display charts for.</p>
                </div>
            </div>
        </div>
    `,
    setup(props) {
        const loading = ref(true);
        const error = ref(null);
        const stats = ref(null);
        const form = ref(null);

        const loadData = async () => {
            loading.value = true;
            try {
                // Load Form Details
                const formRes = await fetch(`/@/forms/${props.formId}`);
                if (formRes.ok) form.value = await formRes.json();

                // Load Stats
                const res = await fetch(`/@/forms/${props.formId}/stats`);
                if (!res.ok) throw new Error('Failed to load stats');
                stats.value = await res.json();
            } catch (e) {
                error.value = e.message;
            } finally {
                loading.value = false;
            }
        };

        const getPercent = (count, total) => {
            if (!total) return 0;
            return Math.round((count / total) * 100);
        };

        onMounted(loadData);

        return { loading, error, stats, form, getPercent };
    }
};
