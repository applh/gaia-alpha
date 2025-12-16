export default {
    name: 'ActionButton',
    props: {
        label: String,
        variant: {
            type: String,
            default: 'primary' // primary, secondary, danger, success
        },
        action: {
            type: String,
            required: true
        },
        icon: String
    },
    emits: ['action'],
    template: `
        <button 
            type="button" 
            class="btn" 
            :class="'btn-' + variant"
            @click="$emit('action', action)"
        >
            <i v-if="icon" :class="'icon-' + icon"></i>
            {{ label }}
        </button>
    `
};
