
import { ref, onMounted, computed } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
    <div class="admin-page">
        <div class="admin-header">
            <h2 class="page-title">
                <LucideIcon name="activity" size="32" style="display:inline-block; vertical-align:middle; margin-right:12px;" />
                API Usage Analytics
            </h2>
            <div class="button-group">
                <button @click="loadData" class="btn btn-primary">
                    <LucideIcon name="refresh-cw" size="16" />
                    Refresh
                </button>
            </div>
        </div>

        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="admin-card stats-card">
                <div class="stats-label">Total API Requests (30d)</div>
                <div class="stats-value" style="font-size: 2.5rem; font-weight: bold;">{{ stats.total_requests }}</div>
            </div>
            <div class="admin-card stats-card">
                <div class="stats-label">Success Rate</div>
                <div class="stats-value" style="font-size: 2.5rem; font-weight: bold; color: #10b981;">{{ stats.success_rate }}%</div>
            </div>
            <div class="admin-card stats-card">
                <div class="stats-label">Avg. Latency</div>
                <div class="stats-value" style="font-size: 2.5rem; font-weight: bold; color: #6366f1;">{{ stats.avg_latency_ms }}ms</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <div class="admin-card">
                <h3>Request Volume Over Time</h3>
                <div v-if="stats.history && stats.history.length > 0" style="height: 300px; display: flex; position: relative; padding: 40px 10px 40px 60px;">
                    <div class="y-axis" style="position: absolute; left: 0; top: 40px; bottom: 40px; width: 50px; display: flex; flex-direction: column; justify-content: space-between; font-size: 0.75rem; color: #999; text-align: right; border-right: 1px solid #eee; padding-right: 15px;">
                        <div v-for="step in yAxisSteps" :key="step">{{ step }}</div>
                    </div>
                    <div style="flex: 1; display: flex; align-items: flex-end; gap: 4px; height: 100%;">
                        <div v-for="day in stats.history" 
                            :key="day.date" 
                            class="history-bar" 
                            :style="{ 
                                height: (day.count / maxHistoryCount * 100) + '%', 
                                background: '#6366f1', 
                                flex: 1,
                                borderRadius: '2px 2px 0 0'
                            }"
                            :title="day.date + ': ' + day.count">
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h3>Slowest Endpoints (Avg ms)</h3>
                <ul class="admin-list" style="list-style: none; padding: 0;">
                    <li v-for="ep in stats.slowest_endpoints" :key="ep.route_pattern" style="padding: 12px 0; border-bottom: 1px solid #eee;">
                        <div style="font-size: 0.9rem; font-weight: 500; word-break: break-all;">{{ ep.route_pattern }}</div>
                        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                            <span style="color: #666; font-size: 0.8rem;">Calls: {{ ep.count }}</span>
                            <span style="color: #f43f5e; font-weight: bold;">{{ Math.round(ep.avg_duration * 1000) }}ms</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    `,
    setup() {
        const stats = ref({
            total_requests: 0,
            success_rate: 0,
            avg_latency_ms: 0,
            slowest_endpoints: [],
            history: []
        });
        const maxHistoryCount = ref(1);

        const yAxisSteps = computed(() => {
            const max = maxHistoryCount.value;
            return [max, Math.round(max * 0.5), 0];
        });

        const loadData = async () => {
            try {
                const res = await fetch('/@/api-analytics/stats');
                if (res.ok) {
                    stats.value = await res.json();
                    if (stats.value.history && stats.value.history.length > 0) {
                        const counts = stats.value.history.map(h => h.count);
                        maxHistoryCount.value = Math.max(...counts, 10);
                    }
                }
            } catch (err) {
                console.error("Failed to load API stats:", err);
            }
        };

        onMounted(loadData);

        return { stats, loadData, maxHistoryCount, yAxisSteps };
    }
};
