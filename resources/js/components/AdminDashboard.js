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
            
            <div v-if="cards" class="stats-grid">
                <div class="stat-card" v-for="card in cards" :key="card.label">
                    <h3>{{ card.label }}</h3>
                    <div class="stat-number">{{ card.value }}</div>
                    <div class="stat-icon"><LucideIcon :name="card.icon" size="120"></LucideIcon></div>
                </div>
            </div>
            <div v-else class="admin-card">Loading stats...</div>
        </div>
    `,
    setup() {
        const cards = ref(null);

        const fetchStats = async () => {
            const res = await fetch('/@/admin/stats');
            if (res.ok) {
                const data = await res.json();
                cards.value = data.cards;
            }
        };

        onMounted(fetchStats);

        return { cards };
    }
};
