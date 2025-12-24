export default {
    props: {
        modelValue: [String, Number, Boolean],
        value: [String, Number, Boolean],
        label: String,
        name: String,
        disabled: Boolean,
        id: {
            type: String,
            default: () => 'radio-' + Math.random().toString(36).substr(2, 9)
        }
    },
    emits: ['update:modelValue', 'change'],
    template: `
        <div class="form-check form-radio" :class="{ 'disabled': disabled }">
            <input
                type="radio"
                class="form-check-input form-radio-input"
                :id="id"
                :name="name"
                :value="value"
                :checked="modelValue === value"
                :disabled="disabled"
                @change="$emit('update:modelValue', value); $emit('change', value)"
            />
            <label class="form-check-label" :for="id">
                <slot>{{ label }}</slot>
            </label>
        </div>
    `
};
