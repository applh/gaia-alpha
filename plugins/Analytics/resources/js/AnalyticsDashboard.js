import { ref, onMounted, computed } from 'vue';
import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Container from 'ui/Container.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import DataTable from 'ui/DataTable.js';
import { UITitle, UIText } from 'ui/Typography.js';

export default {
    components: {
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-container': Container,
        'ui-row': Row,
        'ui-col': Col,
        'ui-data-table': DataTable,
        'ui-title': UITitle,
        'ui-text': UIText
    },
    template: `
    <ui-container class="admin-page">
        <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <ui-title :level="1">
                <LucideIcon name="activity" size="28" style="margin-right: 12px; vertical-align: middle; color: var(--accent-color);" />
                Analytics Dashboard
            </ui-title>
            <ui-button variant="primary" @click="loadData" :loading="loading">
                <LucideIcon name="refresh-cw" size="16" style="margin-right: 8px;" />
                Refresh
            </ui-button>
        </div>

        <ui-row :gutter="20" style="margin-bottom: 32px;">
            <ui-col :xs="24" :sm="12" :md="12" :lg="8" :xl="6">
                <ui-card class="stats-card">
                    <ui-text size="sm" class="text-muted">Total Visits</ui-text>
                    <ui-title :level="2" style="margin: 8px 0 0 0;">{{ stats.total_visits }}</ui-title>
                </ui-card>
            </ui-col>
            <ui-col :xs="24" :sm="12" :md="12" :lg="8" :xl="6">
                <ui-card class="stats-card">
                    <ui-text size="sm" class="text-muted">Unique Visitors</ui-text>
                    <ui-title :level="2" style="margin: 8px 0 0 0;">{{ stats.unique_visitors }}</ui-title>
                </ui-card>
            </ui-col>
            <ui-col :xs="24" :sm="12" :md="12" :lg="8" :xl="6">
                <ui-card class="stats-card">
                    <ui-text size="sm" class="text-muted">Visits Today</ui-text>
                    <ui-title :level="2" style="margin: 8px 0 0 0; color: var(--accent-color);">{{ stats.today_visits }}</ui-title>
                </ui-card>
            </ui-col>
            <ui-col :xs="24" :sm="12" :md="12" :lg="8" :xl="6">
                <ui-card class="stats-card">
                    <ui-text size="sm" class="text-muted">Unique Today</ui-text>
                    <ui-title :level="2" style="margin: 8px 0 0 0; color: var(--accent-color);">{{ stats.today_unique }}</ui-title>
                </ui-card>
            </ui-col>
        </ui-row>

        <ui-row :gutter="20" style="margin-bottom: 32px;">
            <ui-col :xs="24" :md="12" :lg="8" :xl="6">
                <ui-card title="Devices" style="height: 100%;">
                    <ul class="admin-list" style="list-style: none; padding: 0; margin: 0;">
                        <li v-for="d in stats.devices" :key="d.name" style="padding: 12px 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <ui-text>{{ d.name }}</ui-text>
                            <ui-text weight="bold">{{ d.count }}</ui-text>
                        </li>
                    </ul>
                </ui-card>
            </ui-col>
            <ui-col :xs="24" :md="12" :lg="8" :xl="6">
                <ui-card title="Browsers" style="height: 100%;">
                    <ul class="admin-list" style="list-style: none; padding: 0; margin: 0;">
                        <li v-for="b in stats.browsers" :key="b.name" style="padding: 12px 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <ui-text>{{ b.name }}</ui-text>
                            <ui-text weight="bold">{{ b.count }}</ui-text>
                        </li>
                    </ul>
                </ui-card>
            </ui-col>
            <ui-col :xs="24" :md="12" :lg="8" :xl="6">
                <ui-card title="OS" style="height: 100%;">
                    <ul class="admin-list" style="list-style: none; padding: 0; margin: 0;">
                        <li v-for="o in stats.os" :key="o.name" style="padding: 12px 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <ui-text>{{ o.name }}</ui-text>
                            <ui-text weight="bold">{{ o.count }}</ui-text>
                        </li>
                    </ul>
                </ui-card>
            </ui-col>
            <ui-col :xs="24" :md="12" :lg="8" :xl="6">
                <ui-card title="Referrers" style="height: 100%;">
                    <ul class="admin-list" style="list-style: none; padding: 0; margin: 0;">
                        <li v-for="ref in stats.referrers" :key="ref.referrer" style="padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                            <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; color: var(--text-color);" :title="ref.referrer || 'Direct'">
                                {{ ref.referrer || 'Direct' }}
                            </div>
                            <ui-text size="sm" class="text-muted">{{ ref.count }} visits</ui-text>
                        </li>
                        <li v-if="stats.referrers && stats.referrers.length === 0" style="padding: 24px; text-align: center;">
                            <ui-text class="text-muted">No discovery data yet.</ui-text>
                        </li>
                    </ul>
                </ui-card>
            </ui-col>
        </ui-row>

        <ui-card title="Top Pages" style="margin-bottom: 32px;">
            <ui-data-table 
                v-if="stats.top_pages && stats.top_pages.length"
                :data="stats.top_pages"
                :columns="[
                    { label: 'Path', prop: 'page_path' },
                    { label: 'Visits', prop: 'count', align: 'right', width: '120px' }
                ]"
            />
            <div v-else style="padding: 24px; text-align: center;">
                <ui-text class="text-muted">No traffic data recorded.</ui-text>
            </div>
        </ui-card>

        <ui-card title="Visits Over Last 30 Days">
            <div v-if="stats.history && stats.history.length > 0" style="height: 280px; display: flex; position: relative; padding: 20px 10px 40px 60px;">
                <!-- Y-Axis Scale -->
                <div class="y-axis" style="position: absolute; left: 0; top: 20px; bottom: 40px; width: 50px; display: flex; flex-direction: column; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); text-align: right; border-right: 1px solid var(--border-color); padding-right: 15px;">
                    <div v-for="step in yAxisSteps" :key="step">{{ step }}</div>
                </div>

                <!-- Grid Lines -->
                <div style="position: absolute; left: 60px; right: 10px; top: 20px; bottom: 40px; display: flex; flex-direction: column; justify-content: space-between; pointer-events: none;">
                    <div v-for="step in yAxisSteps" :key="'grid-'+step" style="border-top: 1px dashed var(--border-color); opacity: 0.3; width: 100%; height: 0;"></div>
                </div>

                <!-- Bars -->
                <div style="flex: 1; display: flex; align-items: flex-end; gap: 6px; height: 100%; position: relative; z-index: 2;">
                    <div v-for="day in stats.history" 
                        :key="day.date" 
                        class="history-bar" 
                        :style="{ 
                            height: (day.count / maxHistoryCount * 100) + '%', 
                            background: day.count > 0 ? 'var(--accent-color)' : 'var(--border-color)', 
                            opacity: day.count > 0 ? '1' : '0.1',
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
                <div style="position: absolute; bottom: 10px; left: 60px; right: 10px; display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted);">
                    <span>{{ stats.history[0].date }}</span>
                    <span>Today</span>
                </div>
            </div>
            <div v-else style="padding: 48px; text-align: center;">
                <ui-text class="text-muted">Not enough historical data.</ui-text>
            </div>
        </ui-card>
    </ui-container>
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
                const adminPath = window.location.pathname.replace(/\/$/, '');
                const res = await fetch(`${adminPath}/analytics/stats`);
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
