import { ref, reactive, onMounted } from 'vue';

export default {
    name: 'FormRenderer',
    props: {
        formId: [String, Number],
        formSlug: String
    },
    template: `
        <div class="form-renderer">
            <div v-if="loading" class="loading-state">
                <p>Loading form...</p>
            </div>
            
            <div v-else-if="error" class="error-state">
                <p class="error-message">{{ error }}</p>
            </div>
            
            <div v-else-if="form" class="form-container">
                <h3 v-if="form.title">{{ form.title }}</h3>
                <p v-if="form.description" class="form-description">{{ form.description }}</p>
                
                <form @submit.prevent="handleSubmit" class="dynamic-form">
                    <div v-for="field in form.schema" :key="field.key || field.id" class="form-field">
                        <label :for="field.key || field.id">
                            {{ field.label }}
                            <span v-if="field.required" class="required">*</span>
                        </label>
                        
                        <!-- Text, Email, Number inputs -->
                        <input 
                            v-if="['text', 'email', 'number'].includes(field.type)"
                            :id="field.key || field.id"
                            :type="field.type"
                            :name="field.key || field.id"
                            v-model="formData[field.key || field.id]"
                            :placeholder="field.placeholder"
                            :required="field.required"
                        >
                        
                        <!-- Textarea -->
                        <textarea 
                            v-if="field.type === 'textarea'"
                            :id="field.key || field.id"
                            :name="field.key || field.id"
                            v-model="formData[field.key || field.id]"
                            :placeholder="field.placeholder"
                            :rows="field.rows || 3"
                            :required="field.required"
                        ></textarea>
                        
                        <!-- Select -->
                        <select 
                            v-if="field.type === 'select'"
                            :id="field.key || field.id"
                            :name="field.key || field.id"
                            v-model="formData[field.key || field.id]"
                            :required="field.required"
                        >
                            <option value="">Select an option...</option>
                            <option v-for="(option, idx) in field.options" :key="idx" :value="option">
                                {{ option }}
                            </option>
                        </select>
                        
                        <!-- Checkbox -->
                        <div v-if="field.type === 'checkbox'" class="checkbox-field">
                            <input 
                                :id="field.key || field.id"
                                type="checkbox"
                                :name="field.key || field.id"
                                v-model="formData[field.key || field.id]"
                                :required="field.required"
                            >
                            <label :for="field.key || field.id">{{ field.placeholder || field.label }}</label>
                        </div>
                    </div>
                    
                    <div v-if="submitError" class="error-message">
                        {{ submitError }}
                    </div>
                    
                    <div v-if="submitSuccess" class="success-message">
                        Form submitted successfully!
                    </div>
                    
                    <button type="submit" :disabled="submitting" class="submit-button">
                        {{ submitting ? 'Submitting...' : (form.submit_label || 'Submit') }}
                    </button>
                </form>
            </div>
        </div>
    `,
    setup(props) {
        const loading = ref(true);
        const error = ref(null);
        const form = ref(null);
        const formData = reactive({});
        const submitting = ref(false);
        const submitError = ref(null);
        const submitSuccess = ref(false);

        const loadForm = async () => {
            loading.value = true;
            error.value = null;

            try {
                let url;
                if (props.formId) {
                    url = `/@/forms/${props.formId}`;
                } else if (props.formSlug) {
                    url = `/@/public/form/${props.formSlug}`;
                } else {
                    error.value = 'No form ID or slug provided';
                    loading.value = false;
                    return;
                }

                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error('Failed to load form');
                }

                const data = await response.json();
                form.value = data;

                // Initialize formData with empty values
                if (data.schema && Array.isArray(data.schema)) {
                    data.schema.forEach(field => {
                        const key = field.key || field.id;
                        formData[key] = field.type === 'checkbox' ? false : '';
                    });
                }
            } catch (e) {
                error.value = e.message || 'Failed to load form';
            } finally {
                loading.value = false;
            }
        };

        const handleSubmit = async () => {
            submitting.value = true;
            submitError.value = null;
            submitSuccess.value = false;

            try {
                const slug = form.value.slug;
                if (!slug) {
                    throw new Error('Form slug not available');
                }

                const response = await fetch(`/@/public/form/${slug}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ data: formData })
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || 'Submission failed');
                }

                submitSuccess.value = true;

                // Reset form after successful submission
                Object.keys(formData).forEach(key => {
                    formData[key] = form.value.schema.find(f => (f.key || f.id) === key)?.type === 'checkbox' ? false : '';
                });

                // Hide success message after 5 seconds
                setTimeout(() => {
                    submitSuccess.value = false;
                }, 5000);

            } catch (e) {
                submitError.value = e.message || 'Failed to submit form';
            } finally {
                submitting.value = false;
            }
        };

        onMounted(() => {
            loadForm();
        });

        return {
            loading,
            error,
            form,
            formData,
            submitting,
            submitError,
            submitSuccess,
            handleSubmit
        };
    }
};
