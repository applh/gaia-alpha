
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
           ref="iconRef"
           class="lucide-icon-placeholder"
           :style="{
               display: 'inline-flex', 
               width: size + 'px', 
               height: size + 'px',
               stroke: color,
               'stroke-width': strokeWidth
           }">
        </i>
    `,
    setup(props) {
        const iconRef = ref(null);

        const render = () => {
            if (!iconRef.value || !window.lucide || !window.lucide.createIcons) {
                // If ref is missing, element isn't ready. Retry shortly.
                // If lucide is missing, wait for it.
                // But don't retry indefinitely if unmounted (handled by component destruction naturally)
                setTimeout(render, 50);
                return;
            }

            // Safety: Ensure parentNode exists before using it as root
            const rootEl = iconRef.value.parentNode;
            if (rootEl) {
                window.lucide.createIcons({
                    root: rootEl,
                    nameAttr: 'data-lucide',
                    attrs: {
                        width: props.size,
                        height: props.size,
                        stroke: props.color,
                        'stroke-width': props.strokeWidth
                    }
                });
            }
        };

        onMounted(() => {
            // Initial render attempt
            render();
            // Backup retry for async loading or slow hydration
            setTimeout(render, 300);
        });

        watch(() => [props.name, props.color, props.size, props.strokeWidth], () => {
            // Vue re-renders the element, wait a tick for ref to update then re-scan
            setTimeout(render, 0);
        });

        return { iconRef };
    }
};
