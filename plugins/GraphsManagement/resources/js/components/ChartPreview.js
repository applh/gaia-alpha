import { ref, onMounted, onUnmounted, watch } from 'vue';

export default {
    props: {
        chartType: {
            type: String,
            required: true
        },
        data: {
            type: Object,
            required: true
        },
        options: {
            type: Object,
            default: () => ({})
        }
    },
    template: `
        <div class="chart-preview-container" style="position: relative; width: 100%; height: 100%;">
            <canvas ref="chartCanvas"></canvas>
            <div v-if="error" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: var(--error-color, #ef4444); text-align: center;">
                <p>{{ error }}</p>
            </div>
        </div>
    `,
    setup(props) {
        const chartCanvas = ref(null);
        const chartInstance = ref(null);
        const error = ref(null);

        const createChart = async () => {
            if (!chartCanvas.value) return;

            try {
                // Dynamically import Chart.js from CDN with all components
                const ChartModule = await import('https://cdn.jsdelivr.net/npm/chart.js@4.4.7/+esm');
                const { Chart, LineController, BarController, PieController, DoughnutController,
                    RadarController, PolarAreaController, ScatterController,
                    CategoryScale, LinearScale, RadialLinearScale, PointElement,
                    LineElement, BarElement, ArcElement, Tooltip, Legend } = ChartModule;

                // Register all components
                Chart.register(
                    LineController, BarController, PieController, DoughnutController,
                    RadarController, PolarAreaController, ScatterController,
                    CategoryScale, LinearScale, RadialLinearScale,
                    PointElement, LineElement, BarElement, ArcElement,
                    Tooltip, Legend
                );

                // Destroy existing chart
                if (chartInstance.value) {
                    chartInstance.value.destroy();
                }

                // Create new chart
                const ctx = chartCanvas.value.getContext('2d');
                chartInstance.value = new Chart(ctx, {
                    type: props.chartType,
                    data: props.data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        ...props.options
                    }
                });

                error.value = null;
            } catch (err) {
                console.error('Chart rendering error:', err);
                error.value = 'Failed to render chart: ' + err.message;
            }
        };

        onMounted(() => {
            createChart();
        });

        onUnmounted(() => {
            if (chartInstance.value) {
                chartInstance.value.destroy();
            }
        });

        // Watch for changes in props
        watch(() => [props.chartType, props.data, props.options], () => {
            createChart();
        }, { deep: true });

        return {
            chartCanvas,
            error
        };
    }
};
