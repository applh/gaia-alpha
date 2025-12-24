export default {
    props: {
        variant: {
            type: String,
            default: 'primary',
            validator: (value) => ['primary', 'secondary', 'danger', 'ghost', 'link'].includes(value)
        },
        size: {
            type: String,
            default: 'md',
            validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
        },
        type: {
            type: String,
            default: 'button'
        },
        disabled: Boolean,
        loading: Boolean
    },
    emits: ['click'],
    template: `
        <button 
            :type="type" 
            :class="['btn', 'btn-' + variant, 'btn-' + size, { 'loading': loading }]"
            :disabled="disabled || loading"
            @click="$emit('click', $event)"
        >
            <span v-if="loading" class="spinner"></span>
            <slot v-else></slot>
        </button>
    `
};
