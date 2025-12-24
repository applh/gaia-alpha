export default {
    props: {
        variant: {
            type: String,
            default: 'primary',
            validator: (value) => ['primary', 'secondary', 'success', 'warning', 'danger', 'info'].includes(value)
        },
        rounded: Boolean
    },
    template: `
        <span :class="['label-tag', 'tag-' + variant, { 'rounded-full': rounded }]">
            <slot></slot>
        </span>
    `
};
