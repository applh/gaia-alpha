export default {
    props: {
        modelValue: [String, Number],
        label: String,
        placeholder: String,
        disabled: Boolean,
        rows: {
            type: [String, Number],
            default: 3
        },
        required: Boolean,
        error: String,
        id: {
            type: String,
            default: () => 'textarea-' + Math.random().toString(36).substr(2, 9)
        }
    },
    emits: ['update:modelValue', 'blur', 'focus'],
    template: `
        <div class="form-group" :class="{ 'has-error': !!error }">
            <label v-if="label" :for="id">
                {{ label }} <span v-if="required" class="text-danger">*</span>
            </label>
            <textarea
                :id="id"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :rows="rows"
                @input="$emit('update:modelValue', $event.target.value)"
                @blur="$emit('blur', $event)"
                @focus="$emit('focus', $event)"
                class="form-control"
            ></textarea>
            <div v-if="error" class="error-message">{{ error }}</div>
        </div>
    `
};
