export default {
    props: {
        modelValue: [String, Number, Array],
        options: {
            type: Array, // [{ label: 'Option 1', value: '1' }]
            default: () => []
        },
        label: String,
        placeholder: {
            type: String,
            default: 'Select an option'
        },
        disabled: Boolean,
        multiple: Boolean,
        id: {
            type: String,
            default: () => 'select-' + Math.random().toString(36).substr(2, 9)
        }
    },
    emits: ['update:modelValue', 'change'],
    template: `
        <div class="form-group" :class="{ 'disabled': disabled }">
            <label :for="id" v-if="label">{{ label }}</label>
            <div class="custom-select-wrapper">
                <select
                    class="form-control form-select"
                    :id="id"
                    :value="modelValue"
                    :multiple="multiple"
                    :disabled="disabled"
                    @change="updateValue"
                >
                    <option v-if="!multiple && placeholder" value="" disabled selected hidden>{{ placeholder }}</option>
                    <option
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <!-- Custom arrow could go here if we hide default appearance -->
            </div>
        </div>
    `,
    methods: {
        updateValue(event) {
            let value;
            if (this.multiple) {
                value = Array.from(event.target.selectedOptions).map(option => option.value);
            } else {
                value = event.target.value;
            }
            this.$emit('update:modelValue', value);
            this.$emit('change', value);
        }
    }
};
