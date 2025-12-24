export default {
    props: {
        modelValue: Boolean,
        labels: {
            type: Object, // { on: 'On', off: 'Off' }
            default: () => ({})
        },
        disabled: Boolean,
        id: {
            type: String,
            default: () => 'switch-' + Math.random().toString(36).substr(2, 9)
        }
    },
    emits: ['update:modelValue', 'change'],
    template: `
        <div class="switch-wrapper" :class="{ 'disabled': disabled }">
            <label class="switch" :for="id">
                <input
                    type="checkbox"
                    :id="id"
                    :checked="modelValue"
                    :disabled="disabled"
                    @change="$emit('update:modelValue', $event.target.checked); $emit('change', $event.target.checked)"
                />
                <span class="slider"></span>
            </label>
            <span class="switch-label" v-if="labels.on || labels.off">
                {{ modelValue ? (labels.on || 'On') : (labels.off || 'Off') }}
            </span>
        </div>
    `
};
