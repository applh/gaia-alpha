
import { onMounted, watch, ref } from 'vue';

export default {
    props: {
        name: {
            type: String,
            required: true
        },
        size: {
            type: [String, Number],
            default: 24
        },
        color: {
            type: String,
            default: 'currentColor'
        },
        strokeWidth: {
            type: [String, Number],
            default: 2
        }
    },
    template: `
        <i :data-lucide="name" 
           :width="size" 
           :height="size" 
           :stroke="color" 
           :stroke-width="strokeWidth"
           ref="iconRef">
        </i>
    `,
    setup(props) {
        const iconRef = ref(null);

        const createIcon = () => {
            if (window.lucide) {
                window.lucide.createIcons({
                    attrs: {
                        width: props.size,
                        height: props.size,
                        stroke: props.color,
                        'stroke-width': props.strokeWidth
                    },
                    nameAttr: 'data-lucide'
                });
            }
        };

        onMounted(() => {
            createIcon();
        });

        watch(() => props.name, () => {
            // In a real implementation we might need to be more reactive, 
            // but lucide.createIcons scans the DOM. 
            // For simple usage, re-running might be needed or just trusting the scanner.
            // Lucide's createIcons is idempotent but scans everything. 
            // A better approach for specific elements is lucide.icons[name].toSvg() but that returns string.
            // For now, let's stick to the simple createIcons call which is the recommended easy way.
            setTimeout(createIcon, 0);
        });

        return { iconRef };
    }
};
