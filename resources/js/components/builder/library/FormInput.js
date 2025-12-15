export default {
    name: 'FormInput',
    props: {
        label: String,
        modelValue: [String, Number],
        type: {
            type: String,
            default: 'text'
        },
        placeholder: String,
        required: Boolean,
        name: String
    },
    emits: ['update:modelValue'],
    template: `
        <div class="form-group">
            <label v-if="label" :for="name">{{ label }}</label>
            <input 
                :type="type" 
                :id="name" 
                :name="name"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                @input="$emit('update:modelValue', $event.target.value)"
                class="form-control"
            >
        </div>
    `
};
