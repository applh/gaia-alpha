export default {
    name: 'StatCard',
    props: {
        label: String,
        value: [String, Number],
        icon: String,
        color: String,
        loading: Boolean
    },
    template: `
        <div class="stat-card" :class="color">
            <div class="stat-icon" v-if="icon">
                <i :class="'icon-' + icon"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" v-if="loading">Loading...</div>
                <div class="stat-value" v-else>{{ value }}</div>
                <div class="stat-label">{{ label }}</div>
            </div>
        </div>
    `,
    styles: `
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            font-size: 24px;
            padding: 10px;
            border-radius: 50%;
            background: #f0f0f0;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
        }
        .stat-label {
            color: #666;
        }
    `
};
