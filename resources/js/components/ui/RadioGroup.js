export default {
    props: {
        modelValue: [String, Number, Boolean],
        name: {
            type: String,
            default: () => 'radio-group-' + Math.random().toString(36).substr(2, 9)
        },
        direction: {
            type: String,
            default: 'vertical', // 'vertical' or 'horizontal'
        }
    },
    emits: ['update:modelValue', 'change'],
    template: `
        <div class="radio-group" :class="'direction-' + direction">
            <slot></slot>
        </div>
    `,
    provide() {
        return {
            radioGroup: this
        }
    }
};
