export default {
    name: 'FormButton',
    props: {
        label: String,
        type: {
            type: String,
            default: 'button' // button, submit, reset
        },
        variant: {
            type: String,
            default: 'primary' // primary, secondary, danger
        },
        loading: Boolean
    },
    emits: ['click'],
    template: `
        <button 
            :type="type" 
            class="btn" 
            :class="'btn-' + variant"
            @click="$emit('click')"
            :disabled="loading"
        >
            <span v-if="loading" class="spinner"></span>
            {{ label }}
        </button>
    `
};
