export default {
    props: {
        modelValue: {
            type: Boolean,
            default: false
        },
        label: String,
        disabled: Boolean,
        id: {
            type: String,
            default: () => 'checkbox-' + Math.random().toString(36).substr(2, 9)
        }
    },
    emits: ['update:modelValue', 'change'],
    template: `
        <div class="form-check" :class="{ 'disabled': disabled }">
            <input
                type="checkbox"
                class="form-check-input"
                :id="id"
                :checked="modelValue"
                :disabled="disabled"
                @change="$emit('update:modelValue', $event.target.checked); $emit('change', $event.target.checked)"
            />
            <label class="form-check-label" :for="id" v-if="label">
                {{ label }}
            </label>
        </div>
    `
};
