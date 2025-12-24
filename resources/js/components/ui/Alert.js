export default {
    props: {
        type: {
            type: String,
            default: 'info', // info, success, warning, error
            validator: (value) => ['info', 'success', 'warning', 'error'].includes(value)
        },
        title: String,
        description: String,
        closable: Boolean
    },
    emits: ['close'],
    data() {
        return {
            visible: true
        }
    },
    methods: {
        close() {
            this.visible = false;
            this.$emit('close');
        }
    },
    template: `
        <div v-if="visible" class="alert" :class="'alert-' + type">
            <div class="alert-content">
                <span v-if="title" class="alert-title">{{ title }}</span>
                <div class="alert-description">
                    <slot>{{ description }}</slot>
                </div>
            </div>
            <button v-if="closable" class="alert-close" @click="close">&times;</button>
        </div>
    `
};
