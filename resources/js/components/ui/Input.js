export default {
    props: {
        modelValue: [String, Number],
        label: String,
        type: {
            type: String,
            default: 'text'
        },
        placeholder: String,
        disabled: Boolean,
        required: Boolean,
        error: String,
        id: {
            type: String,
            default: () => 'input-' + Math.random().toString(36).substr(2, 9)
        }
    },
    emits: ['update:modelValue', 'blur', 'focus'],
    template: `
        <div class="form-group" :class="{ 'has-error': !!error }">
            <label v-if="label" :for="id">
                {{ label }} <span v-if="required" class="text-danger">*</span>
            </label>
            <input
                :id="id"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                @input="$emit('update:modelValue', $event.target.value)"
                @blur="$emit('blur', $event)"
                @focus="$emit('focus', $event)"
                class="form-control"
            />
            <div v-if="error" class="error-message">{{ error }}</div>
        </div>
    `
};
