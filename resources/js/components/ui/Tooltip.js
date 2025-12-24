export default {
    props: {
        text: String,
        position: {
            type: String,
            default: 'top'
        }
    },
    template: `
        <div class="tooltip-container">
            <slot></slot>
            <div class="tooltip-text" :class="'bg-' + position">{{ text }}</div>
        </div>
    `
};
