
import { ref, reactive, onMounted } from 'vue';

export default {
    props: ['slug'],
    template: `
        <div class="form-card">
            <div v-if="loading" class="loading">Loading form...</div>
            <div v-else-if="error" class="error-message">{{ error }}</div>
            <div v-else-if="submitted" class="success-message">
                <h3>Thank you!</h3>
                <p>Your submission has been received.</p>
            </div>
            <div v-else>
                <h2>{{ form.title }}</h2>
                <p v-if="form.description">{{ form.description }}</p>
                
                <form @submit.prevent="submitForm">
                    <div v-for="(field, index) in form.schema" :key="index" class="form-group">
                        <label>
                            {{ field.label }}
                            <span v-if="field.required" class="required">*</span>
                        </label>
                        
                        <input 
                            v-if="field.type === 'text' || field.type === 'email' || field.type === 'number'"
                            :type="field.type"
                            v-model="formData[field.key]"
                            :required="field.required"
                            :placeholder="field.placeholder"
                        >
                        
                        <textarea
                            v-if="field.type === 'textarea'"
                            v-model="formData[field.key]"
                            :required="field.required"
                            :placeholder="field.placeholder"
                            :rows="field.rows || 3"
                        ></textarea>
                        
                        <select v-if="field.type === 'select'" v-model="formData[field.key]" :required="field.required">
                            <option value="" disabled>Select an option</option>
                            <option v-for="opt in field.options" :value="opt">{{ opt }}</option>
                        </select>
                        
                        <div v-if="field.type === 'checkbox'" class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="formData[field.key]">
                                {{ field.placeholder || 'Yes' }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" :disabled="submitting">
                            {{ submitting ? 'Submitting...' : (form.submit_label || 'Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `,
    setup(props) {
        const form = ref(null);
        const loading = ref(true);
        const error = ref(null);
        const submitted = ref(false);
        const submitting = ref(false);
        const formData = reactive({});

        const fetchForm = async () => {
            try {
                const res = await fetch(`/@/public/form/${props.slug}`);
                if (!res.ok) throw new Error('Form not found');
                const data = await res.json();
                form.value = data;

                // Initialize form data keys
                if (data.schema) {
                    data.schema.forEach(field => {
                        formData[field.key] = field.type === 'checkbox' ? false : '';
                    });
                }
            } catch (e) {
                error.value = e.message;
            } finally {
                loading.value = false;
            }
        };

        const submitForm = async () => {
            submitting.value = true;
            try {
                const res = await fetch(`/@/public/form/${props.slug}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ data: formData })
                });

                if (res.ok) {
                    submitted.value = true;
                } else {
                    alert('Submission failed. Please try again.');
                }
            } catch (e) {
                alert('Network error.');
            } finally {
                submitting.value = false;
            }
        };

        onMounted(fetchForm);

        return { form, loading, error, submitted, submitting, formData, submitForm };
    }
};
