import { ref, reactive, onMounted } from 'vue';

export default {
    name: 'FormRenderer',
    props: {
        formId: [String, Number],
        formSlug: String
    },
    template: `
        <div class="form-renderer form-builder-plugin">
            <div v-if="loading" class="loading-state">
                <p>Loading form...</p>
            </div>
            
            <div v-else-if="error" class="error-state">
                <p class="error-message">{{ error }}</p>
            </div>
            
            <div v-else-if="form" class="form-container">
                <h3 v-if="form.title">{{ form.title }}</h3>
                <p v-if="form.description" class="form-description">{{ form.description }}</p>
                
                <form v-if="!showResults" @submit.prevent="handleSubmit" class="dynamic-form">
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
                            :disabled="submitting"
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
                            :disabled="submitting"
                        ></textarea>
                        
                        <!-- Select -->
                        <select 
                            v-if="field.type === 'select'"
                            :id="field.key || field.id"
                            :name="field.key || field.id"
                            v-model="formData[field.key || field.id]"
                            :required="field.required"
                            :disabled="submitting"
                        >
                            <option value="">Select an option...</option>
                            <option v-for="(option, idx) in field.options" :key="idx" :value="option">
                                {{ option }}
                            </option>
                        </select>
                        
                         <!-- Radio -->
                        <div v-if="field.type === 'radio'" class="radio-group">
                            <div v-for="(option, idx) in field.options" :key="idx" class="radio-item">
                                <input 
                                    type="radio" 
                                    :id="(field.key || field.id) + idx"
                                    :name="field.key || field.id"
                                    :value="option"
                                    v-model="formData[field.key || field.id]"
                                    :required="field.required"
                                >
                                <label :for="(field.key || field.id) + idx">{{ option }}</label>
                            </div>
                        </div>

                        <!-- Checkbox (Single) -->
                        <div v-if="field.type === 'checkbox'" class="checkbox-field">
                            <input 
                                :id="field.key || field.id"
                                type="checkbox"
                                :name="field.key || field.id"
                                v-model="formData[field.key || field.id]"
                                :required="field.required"
                                :disabled="submitting"
                            >
                            <label :for="field.key || field.id">{{ field.placeholder || field.label }}</label>
                        </div>
                    </div>
                    
                    <div v-if="submitError" class="error-message">
                        {{ submitError }}
                    </div>
                    
                    <button type="submit" :disabled="submitting" class="submit-button btn-primary">
                        {{ submitting ? 'Submitting...' : (form.submit_label || 'Submit') }}
                    </button>
                </form>

                <!-- Results View (Quiz/Poll) -->
                <div v-else class="results-view">
                    <div class="success-message">
                        <h4>Thank you!</h4>
                        <p>Your submission has been received.</p>
                    </div>

                    <div v-if="quizResult" class="quiz-score">
                        <div class="score-circle">
                            <span class="score-value">{{ quizResult.score }}</span>
                            <span class="score-total">/ {{ quizResult.total }}</span>
                        </div>
                        <p>Score</p>
                    </div>

                    <div v-if="quizResult && quizResult.results" class="quiz-breakdown">
                        <h5>Results:</h5>
                         <div v-for="field in form.schema" :key="field.key" class="result-item">
                            <div v-if="quizResult.results[field.key]">
                                <p class="question-label">{{ field.label }}</p>
                                <p :class="{'text-success': quizResult.results[field.key].correct, 'text-danger': !quizResult.results[field.key].correct}">
                                    Your answer: {{ quizResult.results[field.key].userAnswer }}
                                    <span v-if="!quizResult.results[field.key].correct">(Correct: {{ quizResult.results[field.key].correctAnswer }})</span>
                                    <span v-if="quizResult.results[field.key].points > 0" class="points-badge">+{{ quizResult.results[field.key].points }} pts</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <button @click="resetForm" class="btn-secondary">Submit Another</button>
                </div>
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
        const showResults = ref(false);
        const quizResult = ref(null);

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

                // Handle Quiz/Poll results
                if (result.score !== undefined) {
                    quizResult.value = result;
                }

                showResults.value = true;

            } catch (e) {
                submitError.value = e.message || 'Failed to submit form';
            } finally {
                submitting.value = false;
            }
        };

        const resetForm = () => {
            showResults.value = false;
            quizResult.value = null;
            // Reset form data
            Object.keys(formData).forEach(key => {
                formData[key] = form.value.schema.find(f => (f.key || f.id) === key)?.type === 'checkbox' ? false : '';
            });
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
            showResults,
            quizResult,
            handleSubmit,
            resetForm
        };
    }
};
