import { ref, onMounted, computed } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
    <div class="admin-page">
        <div class="admin-header">
            <h2 class="page-title">
                <LucideIcon name="activity" size="32" />
                Analytics Dashboard
            </h2>
            <div class="button-group">
                <button @click="loadData" class="btn btn-primary">
                    <LucideIcon name="refresh-cw" size="16" />
                    Refresh
                </button>
            </div>
        </div>

        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="admin-card stats-card">
                <div class="stats-label">Total Visits</div>
                <div class="stats-value" style="font-size: 2rem; font-weight: bold;">{{ stats.total_visits }}</div>
            </div>
            <div class="admin-card stats-card">
                <div class="stats-label">Unique Visitors</div>
                <div class="stats-value" style="font-size: 2rem; font-weight: bold;">{{ stats.unique_visitors }}</div>
            </div>
            <div class="admin-card stats-card">
                <div class="stats-label">Visits Today</div>
                <div class="stats-value" style="font-size: 2rem; font-weight: bold; color: var(--accent-color, #6366f1);">{{ stats.today_visits }}</div>
            </div>
            <div class="admin-card stats-card">
                <div class="stats-label">Unique Today</div>
                <div class="stats-value" style="font-size: 2rem; font-weight: bold; color: var(--accent-color, #6366f1);">{{ stats.today_unique }}</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="admin-card">
                <h3>Devices</h3>
                <ul class="admin-list" style="list-style: none; padding: 0;">
                    <li v-for="d in stats.devices" :key="d.name" style="padding: 8px 0; border-bottom: 1px solid var(--border-color, #eee); display: flex; justify-content: space-between;">
                        <span>{{ d.name }}</span>
                        <span style="font-weight: bold;">{{ d.count }}</span>
                    </li>
                </ul>
            </div>
            <div class="admin-card">
                <h3>Browsers</h3>
                <ul class="admin-list" style="list-style: none; padding: 0;">
                    <li v-for="b in stats.browsers" :key="b.name" style="padding: 8px 0; border-bottom: 1px solid var(--border-color, #eee); display: flex; justify-content: space-between;">
                        <span>{{ b.name }}</span>
                        <span style="font-weight: bold;">{{ b.count }}</span>
                    </li>
                </ul>
            </div>
            <div class="admin-card">
                <h3>OS</h3>
                <ul class="admin-list" style="list-style: none; padding: 0;">
                    <li v-for="o in stats.os" :key="o.name" style="padding: 8px 0; border-bottom: 1px solid var(--border-color, #eee); display: flex; justify-content: space-between;">
                        <span>{{ o.name }}</span>
                        <span style="font-weight: bold;">{{ o.count }}</span>
                    </li>
                </ul>
            </div>
            <div class="admin-card">
                <h3>Referrers</h3>
                <ul class="admin-list" style="list-style: none; padding: 0;">
                    <li v-for="ref in stats.referrers" :key="ref.referrer" style="padding: 8px 0; border-bottom: 1px solid var(--border-color, #eee);">
                        <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" :title="ref.referrer || 'Direct'">
                            {{ ref.referrer || 'Direct' }}
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted, #999);">{{ ref.count }} visits</div>
                    </li>
                    <li v-if="stats.referrers && stats.referrers.length === 0" style="padding: 20px; text-align: center; color: var(--text-muted, #999);">
                        No discovery data yet.
                    </li>
                </ul>
            </div>
        </div>

        <div class="admin-card">
            <h3>Top Pages</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Path</th>
                        <th style="text-align: right;">Visits</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="page in stats.top_pages" :key="page.page_path">
                        <td>{{ page.page_path }}</td>
                        <td style="text-align: right;">{{ page.count }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="admin-card" style="margin-top: 20px;">
            <h3>Visits Over Last 30 Days</h3>
            <div v-if="stats.history && stats.history.length > 0" style="height: 240px; display: flex; position: relative; padding: 20px 10px 30px 50px;">
                <!-- Y-Axis Scale -->
                <div class="y-axis" style="position: absolute; left: 0; top: 20px; bottom: 30px; width: 40px; display: flex; flex-direction: column; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted, #999); text-align: right; border-right: 1px solid var(--border-color, #eee); padding-right: 10px;">
                    <div v-for="step in yAxisSteps" :key="step">{{ step }}</div>
                </div>

                <!-- Grid Lines -->
                <div style="position: absolute; left: 50px; right: 10px; top: 20px; bottom: 30px; display: flex; flex-direction: column; justify-content: space-between; pointer-events: none;">
                    <div v-for="step in yAxisSteps" :key="'grid-'+step" style="border-top: 1px dashed var(--border-color, rgba(0,0,0,0.05)); width: 100%; height: 0;"></div>
                </div>

                <!-- Bars -->
                <div style="flex: 1; display: flex; align-items: flex-end; gap: 4px; height: 100%; position: relative; z-index: 2;">
                    <div v-for="day in stats.history" 
                        :key="day.date" 
                        class="history-bar" 
                        :style="{ 
                            height: (day.count / maxHistoryCount * 100) + '%', 
                            background: day.count > 0 ? 'var(--accent-color, #6366f1)' : 'var(--border-color, rgba(255,255,255,0.05))', 
                            flex: 1, 
                            position: 'relative',
                            minHeight: day.count > 0 ? '4px' : '2px',
                            borderRadius: '2px 2px 0 0'
                        }"
                        :title="day.date + ': ' + day.count">
                    </div>
                </div>

                <!-- X-Axis Labels (Today and 30 days ago) -->
                <div style="position: absolute; bottom: 5px; left: 50px; right: 10px; display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-muted, #999);">
                    <span>{{ stats.history[0].date }}</span>
                    <span>Today</span>
                </div>
            </div>
            <div v-else style="padding: 40px; text-align: center; color: var(--text-muted);">
                Not enough historical data.
            </div>
        </div>
    </div>
    `,
    setup() {
        const stats = ref({
            total_visits: 0,
            today_visits: 0,
            top_pages: [],
            history: [],
            referrers: [],
            browsers: [],
            os: []
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
                const res = await fetch('/@/analytics/stats');
                if (res.ok) {
                    stats.value = await res.json();
                    if (stats.value.history && stats.value.history.length > 0) {
                        const counts = stats.value.history.map(h => h.count);
                        maxHistoryCount.value = Math.max(...counts, 10); // Minimum scale of 10
                    }
                }
            } catch (err) {
                console.error("Failed to load analytics:", err);
            } finally {
                loading.value = false;
            }
        };

        onMounted(() => {
            loadData();
        });

        return {
            stats,
            loading,
            loadData,
            maxHistoryCount,
            yAxisSteps
        };
    }
};
