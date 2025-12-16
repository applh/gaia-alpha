export default {
    name: 'FormSelect',
    props: {
        label: String,
        modelValue: [String, Number],
        options: {
            type: Array, // Array of { value, label }
            default: () => []
        },
        required: Boolean,
        name: String
    },
    emits: ['update:modelValue'],
    template: `
        <div class="form-group">
            <label v-if="label" :for="name">{{ label }}</label>
            <select 
                :id="name" 
                :name="name"
                :value="modelValue"
                :required="required"
                @change="$emit('update:modelValue', $event.target.value)"
                class="form-control"
            >
                <option value="" disabled selected>Select an option</option>
                <option v-for="opt in options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                </option>
            </select>
        </div>
    `
};
