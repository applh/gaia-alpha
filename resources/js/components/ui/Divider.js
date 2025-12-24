export default {
    props: {
        direction: {
            type: String,
            default: 'horizontal' // horizontal, vertical
        },
        contentPosition: {
            type: String,
            default: 'center' // left, center, right
        },
        borderStyle: {
            type: String,
            default: 'solid'
        }
    },
    template: `
        <div 
            class="divider" 
            :class="['divider-' + direction, 'divider-' + borderStyle]"
        >
            <div 
                v-if="$slots.default && direction === 'horizontal'" 
                class="divider-text" 
                :class="'is-' + contentPosition"
            >
                <slot></slot>
            </div>
        </div>
    `
};
