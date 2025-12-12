import { ref, onMounted } from 'vue';
import Icon from './Icon.js';

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
            
            <div v-if="stats" class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="stat-number">{{ stats.users }}</div>
                    <div class="stat-icon"><LucideIcon name="users" size="120"></LucideIcon></div>
                </div>
                <div class="stat-card">
                    <h3>Total Todos</h3>
                    <div class="stat-number">{{ stats.todos }}</div>
                    <div class="stat-icon"><LucideIcon name="check-square" size="120"></LucideIcon></div>
                </div>
                <div class="stat-card">
                    <h3>Total Pages</h3>
                    <div class="stat-number">{{ stats.pages }}</div>
                    <div class="stat-icon"><LucideIcon name="file-text" size="120"></LucideIcon></div>
                </div>
                <div class="stat-card">
                    <h3>Total Templates</h3>
                    <div class="stat-number">{{ stats.templates }}</div>
                    <div class="stat-icon"><LucideIcon name="layout-template" size="120"></LucideIcon></div>
                </div>
                <div class="stat-card">
                    <h3>Total Images</h3>
                    <div class="stat-number">{{ stats.images }}</div>
                    <div class="stat-icon"><LucideIcon name="image" size="120"></LucideIcon></div>
                </div>
                <div class="stat-card">
                    <h3>Total Forms</h3>
                    <div class="stat-number">{{ stats.forms }}</div>
                    <div class="stat-icon"><LucideIcon name="clipboard-list" size="120"></LucideIcon></div>
                </div>
                <div class="stat-card">
                    <h3>Form Submissions</h3>
                    <div class="stat-number">{{ stats.submissions }}</div>
                    <div class="stat-icon"><LucideIcon name="inbox" size="120"></LucideIcon></div>
                </div>
                <div class="stat-card">
                    <h3>Datastore</h3>
                    <div class="stat-number">{{ stats.datastore }}</div>
                    <div class="stat-icon"><LucideIcon name="database" size="120"></LucideIcon></div>
                </div>
            </div>
            <div v-else class="admin-card">Loading stats...</div>
        </div>
    `,
    setup() {
        const stats = ref(null);

        const fetchStats = async () => {
            const res = await fetch('/api/admin/stats');
            if (res.ok) {
                stats.value = await res.json();
            }
        };

        onMounted(fetchStats);

        return { stats };
    }
};
