export default {
    props: {
        size: {
            type: String,
            default: 'md', // sm, md, lg
            validator: (value) => ['sm', 'md', 'lg'].includes(value)
        },
        color: {
            type: String,
            default: 'primary' // primary, white, accent
        }
    },
    template: `
        <div class="spinner" :class="['spinner-' + size, 'spinner-' + color]"></div>
    `
};
