
export default {
    props: {
        modelValue: String,
        required: {
            type: Boolean,
            default: false
        },
        placeholder: {
            type: String,
            default: ''
        },
        id: {
            type: String,
            default: null
        }
    },
    emits: ['update:modelValue'],
    data() {
        return {
            showPassword: false
        }
    },
    template: `
        <div class="password-input-wrapper" style="position: relative; width: 100%;">
            <input 
                :type="showPassword ? 'text' : 'password'" 
                :value="modelValue" 
                @input="$emit('update:modelValue', $event.target.value)"
                :required="required"
                :placeholder="placeholder"
                :id="id"
                style="width: 100%; padding-right: 40px;"
            >
            <button 
                type="button" 
                @click="showPassword = !showPassword"
                style="
                    position: absolute; 
                    right: 8px; 
                    top: 50%; 
                    transform: translateY(-50%); 
                    background: none; 
                    border: none; 
                    color: var(--gray); 
                    cursor: pointer;
                    padding: 4px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: auto;
                    height: auto;
                "
                :title="showPassword ? 'Hide password' : 'Show password'"
            >
                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
            </button>
        </div>
    `
}
