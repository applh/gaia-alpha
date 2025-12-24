
import { ref, onMounted, defineAsyncComponent } from 'vue';
import Icon from 'ui/Icon.js';


export default {
    components: { LucideIcon: Icon },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">
                    <LucideIcon name="layout-dashboard" size="32" style="display:inline-block; vertical-align:middle; margin-right:12px;"></LucideIcon>
                    Admin Dashboard
                </h2>
            </div>
            
            <div v-if="cards" class="stats-grid">
                <div class="stat-card" v-for="card in cards" :key="card.label">
                    <h3>{{ card.label }}</h3>
                    <div class="stat-number">{{ card.value }}</div>
                    <div class="stat-icon"><LucideIcon :name="card.icon" size="120"></LucideIcon></div>
                </div>
            </div>
            <div v-else class="admin-card">Loading stats...</div>

            <!-- Custom Plugin Widgets -->
            <div v-if="widgetComponents.length > 0" class="dashboard-widgets" style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                <component 
                    v-for="widget in widgetComponents" 
                    :key="widget.name" 
                    :is="widget.component"
                    class="dashboard-widget"
                    :style="widget.width === 'full' ? 'grid-column: 1 / -1' : ''"
                />
            </div>
        </div>
    `,
    setup() {
        const cards = ref(null);
        const widgetComponents = [];

        // Load custom widgets from siteConfig
        if (window.siteConfig && window.siteConfig.dashboard_widgets) {
            window.siteConfig.dashboard_widgets.forEach(widget => {
                widgetComponents.push({
                    name: widget.name,
                    component: defineAsyncComponent(() => import(widget.path)),
                    width: widget.width || 'full'
                });
            });
        }

        const fetchStats = async () => {
            const res = await fetch('/@/admin/stats');
            if (res.ok) {
                const data = await res.json();
                cards.value = data.cards;
            }
        };

        onMounted(fetchStats);

        return { cards, widgetComponents };
    }
};
