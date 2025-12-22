
import { ref, onMounted, computed } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
    <div class="admin-page">
        <div class="admin-header">
            <h2 class="page-title">
                <LucideIcon name="zap" size="32" style="display:inline-block; vertical-align:middle; margin-right:12px;" />
                MCP Activity Dashboard
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
                <div class="stats-label">Total MCP Calls (30d)</div>
                <div class="stats-value" style="font-size: 2.5rem; font-weight: bold;">{{ stats.total_calls }}</div>
                <div class="stats-icon opacity-10" style="position: absolute; right: 20px; bottom: 10px;"><LucideIcon name="terminal" size="64" /></div>
            </div>
            <div class="admin-card stats-card">
                <div class="stats-label">Success Rate</div>
                <div class="stats-value" style="font-size: 2.5rem; font-weight: bold; color: #10b981;">{{ stats.success_rate }}%</div>
                <div class="stats-icon opacity-10" style="position: absolute; right: 20px; bottom: 10px;"><LucideIcon name="check-circle" size="64" /></div>
            </div>
            <div class="admin-card stats-card">
                <div class="stats-label">Avg. Latency</div>
                <div class="stats-value" style="font-size: 2.5rem; font-weight: bold; color: #6366f1;">{{ stats.avg_duration_ms }}ms</div>
                <div class="stats-icon opacity-10" style="position: absolute; right: 20px; bottom: 10px;"><LucideIcon name="clock" size="64" /></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <div class="admin-card">
                <h3>Calls Over Last 30 Days</h3>
                <div v-if="stats.history && stats.history.length > 0" style="height: 300px; display: flex; position: relative; padding: 40px 10px 40px 60px;">
                    <!-- Y-Axis Scale -->
                    <div class="y-axis" style="position: absolute; left: 0; top: 40px; bottom: 40px; width: 50px; display: flex; flex-direction: column; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted, #999); text-align: right; border-right: 1px solid var(--border-color, #eee); padding-right: 15px;">
                        <div v-for="step in yAxisSteps" :key="step">{{ step }}</div>
                    </div>

                    <!-- Grid Lines -->
                    <div style="position: absolute; left: 60px; right: 10px; top: 40px; bottom: 40px; display: flex; flex-direction: column; justify-content: space-between; pointer-events: none;">
                        <div v-for="step in yAxisSteps" :key="'grid-'+step" style="border-top: 1px dashed var(--border-color, rgba(0,0,0,0.05)); width: 100%; height: 0;"></div>
                    </div>

                    <!-- Bars -->
                    <div style="flex: 1; display: flex; align-items: flex-end; gap: 8px; height: 100%; position: relative; z-index: 2;">
                        <div v-for="day in stats.history" 
                            :key="day.date" 
                            class="history-bar" 
                            :style="{ 
                                height: (day.count / maxHistoryCount * 100) + '%', 
                                background: 'linear-gradient(to top, #6366f1, #a855f7)', 
                                flex: 1, 
                                position: 'relative',
                                minHeight: day.count > 0 ? '4px' : '2px',
                                borderRadius: '4px 4px 0 0',
                                transition: 'height 0.3s ease'
                            }"
                            :title="day.date + ': ' + day.count">
                        </div>
                    </div>

                    <!-- X-Axis Labels -->
                    <div style="position: absolute; bottom: 10px; left: 60px; right: 10px; display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-muted, #999);">
                        <span>{{ stats.history[0].date }}</span>
                        <span>Today</span>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h3>Top Tools</h3>
                <ul class="admin-list" style="list-style: none; padding: 0;">
                    <li v-for="tool in stats.top_tools" :key="tool.method" style="padding: 12px 0; border-bottom: 1px solid var(--border-color, #eee); display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="background: rgba(99, 102, 241, 0.1); padding: 8px; border-radius: 8px;">
                                <LucideIcon name="box" size="16" color="#6366f1" />
                            </div>
                            <span style="font-weight: 500;">{{ tool.method }}</span>
                        </div>
                        <span class="badge" style="background: var(--primary-color, #6366f1); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;">{{ tool.count }}</span>
                    </li>
                    <li v-if="stats.top_tools && stats.top_tools.length === 0" style="padding: 40px; text-align: center; color: var(--text-muted, #999);">
                        No tool activity recorded yet.
                    </li>
                </ul>
            </div>
        </div>
    </div>
    `,
    setup() {
        const stats = ref({
            total_calls: 0,
            success_rate: 0,
            avg_duration_ms: 0,
            top_tools: [],
            history: []
        });
        const loading = ref(false);
        const maxHistoryCount = ref(1);

        const yAxisSteps = computed(() => {
            const max = maxHistoryCount.value;
            return [
                max,
                Math.round(max * 0.75),
                Math.round(max * 0.5),
                Math.round(max * 0.25),
                0
            ];
        });

        const loadData = async () => {
            loading.value = true;
            try {
                const res = await fetch('/@/mcp/stats');
                if (res.ok) {
                    stats.value = await res.json();
                    if (stats.value.history && stats.value.history.length > 0) {
                        const counts = stats.value.history.map(h => h.count);
                        maxHistoryCount.value = Math.max(...counts, 10);
                    }
                }
            } catch (err) {
                console.error("Failed to load MCP stats:", err);
            } finally {
                loading.value = false;
            }
        };

        onMounted(loadData);

        return {
            stats,
            loading,
            loadData,
            maxHistoryCount,
            yAxisSteps
        };
    }
};
