
import { ref, onMounted } from 'vue';

export default {
    template: `
        <div class="admin-dashboard">
            <h2>Admin Dashboard</h2>
            <div v-if="stats" class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p>{{ stats.users }}</p>
                </div>
                <div class="stat-card">
                    <h3>Total Todos</h3>
                    <p>{{ stats.todos }}</p>
                </div>
                <div class="stat-card">
                    <h3>Total Pages</h3>
                    <p>{{ stats.pages }}</p>
                </div>
                <div class="stat-card">
                    <h3>Total Images</h3>
                    <p>{{ stats.images }}</p>
                </div>
            </div>
            <div v-else>Loading stats...</div>
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
