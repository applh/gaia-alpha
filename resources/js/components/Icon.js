
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
        <span ref="iconRef" 
              class="icon-wrapper"
              :style="{
                  display: 'inline-flex', 
                  width: size + 'px', 
                  height: size + 'px',
                  color: color
              }">
            <i :data-lucide="name"
               :width="size"
               :height="size"
               :stroke="color"
               :stroke-width="strokeWidth">
            </i>
        </span>
    `,
    setup(props) {
        const iconRef = ref(null);

        const render = () => {
            if (!iconRef.value || !window.lucide || !window.lucide.createIcons) {
                setTimeout(render, 50);
                return;
            }

            // Use the wrapper as the root for Lucide to scan
            window.lucide.createIcons({
                root: iconRef.value,
                nameAttr: 'data-lucide',
                attrs: {
                    width: props.size,
                    height: props.size,
                    stroke: props.color,
                    'stroke-width': props.strokeWidth
                }
            });
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
