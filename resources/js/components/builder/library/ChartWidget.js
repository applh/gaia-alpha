import '/resources/js/vendor/chart.js';

export default {
    name: 'ChartWidget',
    props: {
        type: {
            type: String,
            default: 'bar' // bar, line, pie, doughnut
        },
        data: {
            type: Object,
            default: () => ({ labels: [], datasets: [] })
        },
        options: {
            type: Object,
            default: () => ({})
        },
        title: String,
        loading: Boolean
    },
    template: `
        <div class="chart-widget-container">
            <h3 v-if="title">{{ title }}</h3>
            <div class="chart-wrapper">
                <div v-if="loading" class="loading-overlay">Loading...</div>
                <canvas ref="canvas"></canvas>
            </div>
        </div>
    `,
    styles: `
        .chart-widget-container {
            background: var(--card-bg, #fff);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color, #eee);
            position: relative;
        }
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .loading-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.5);
            z-index: 10;
        }
    `,
    data() {
        return {
            chartInstance: null
        }
    },
    mounted() {
        this.renderChart();
    },
    watch: {
        data: {
            handler() {
                this.updateChart();
            },
            deep: true
        },
        type() {
            this.renderChart();
        }
    },
    methods: {
        renderChart() {
            if (this.chartInstance) {
                this.chartInstance.destroy();
            }

            const ctx = this.$refs.canvas.getContext('2d');

            // Check if Chart is loaded globally (script tag) or if we need to mock/import
            // Since we downloaded UMD, 'Chart' should be global window.Chart
            // If it was ESM, we would import it. 
            // The 'import' at top of this file might fail if the file is UMD and not ESM.
            // But we will assume browser handles it or it executes side effect.

            if (typeof Chart === 'undefined') {
                console.error('Chart.js not loaded');
                return;
            }

            this.chartInstance = new Chart(ctx, {
                type: this.type,
                data: this.data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    ...this.options
                }
            });
        },
        updateChart() {
            if (!this.chartInstance) return;
            this.chartInstance.data = this.data;
            this.chartInstance.update();
        }
    }
};
