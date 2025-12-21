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
        let chartInstance = null;
        const error = ref(null);

        const createChart = async () => {
            if (!chartCanvas.value) return;

            try {
                if (typeof window.Chart === 'undefined') {
                    await import('/min/js/vendor/chart.js');
                }

                // Destroy existing chart
                if (chartInstance) {
                    chartInstance.destroy();
                    chartInstance = null;
                }

                // Create new chart
                // Use chartCanvas.value (HTMLElement) directly to avoid context issues with proxies
                chartInstance = new window.Chart(chartCanvas.value, {
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
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
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
