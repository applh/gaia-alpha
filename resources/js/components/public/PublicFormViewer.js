
import { ref, reactive, onMounted } from 'vue';

export default {
    props: ['slug'],
    template: `
        <div class="form-card">
            <div v-if="loading" class="loading">Loading form...</div>
            <div v-else-if="error" class="error-message">{{ error }}</div>
            
            <div v-else-if="form">
                <h2>{{ form.title }}</h2>
                <p v-if="form.description">{{ form.description }}</p>
                
                <form v-if="!showResults" @submit.prevent="submitForm">
                    <div v-for="(field, index) in form.schema" :key="index" class="form-group">
                        <label>
                            {{ field.label }}
                            <span v-if="field.required" class="required">*</span>
                        </label>
                        
                        <input 
                            v-if="['text','email','number'].includes(field.type)"
                            :type="field.type"
                            v-model="formData[field.key]"
                            :required="field.required"
                            :placeholder="field.placeholder"
                            :disabled="submitting"
                        >
                        
                        <textarea
                            v-if="field.type === 'textarea'"
                            v-model="formData[field.key]"
                            :required="field.required"
                            :placeholder="field.placeholder"
                            :rows="field.rows || 3"
                            :disabled="submitting"
                        ></textarea>
                        
                        <select v-if="field.type === 'select'" v-model="formData[field.key]" :required="field.required" :disabled="submitting">
                            <option value="" disabled>Select an option</option>
                            <option v-for="opt in field.options" :value="opt">{{ opt }}</option>
                        </select>
                        
                        <!-- Radio -->
                        <div v-if="field.type === 'radio'" class="radio-group">
                            <div v-for="(option, idx) in field.options" :key="idx" class="radio-item">
                                <label>
                                    <input 
                                        type="radio" 
                                        :name="field.key"
                                        :value="option"
                                        v-model="formData[field.key]"
                                        :required="field.required"
                                        :disabled="submitting"
                                    >
                                    {{ option }}
                                </label>
                            </div>
                        </div>

                        <div v-if="field.type === 'checkbox'" class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="formData[field.key]" :disabled="submitting">
                                {{ field.placeholder || 'Yes' }}
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" :disabled="submitting" class="btn-primary">
                            {{ submitting ? 'Submitting...' : (form.submit_label || 'Submit') }}
                        </button>
                    </div>
                </form>

                 <!-- Results View (Quiz/Poll) -->
                <div v-else class="results-view">
                    <div class="success-message">
                        <h3>Thank you!</h3>
                        <p>Your submission has been received.</p>
                    </div>

                    <div v-if="quizResult" class="quiz-score" style="text-align:center; margin: 20px 0;">
                        <h2 class="score-display">{{ quizResult.score }} / {{ quizResult.total }}</h2>
                        <p>Total Score</p>
                    </div>

                    <div v-if="quizResult && quizResult.results" class="quiz-breakdown">
                         <div v-for="field in form.schema" :key="field.key" class="result-item" style="margin-bottom: 15px; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 4px;">
                            <div v-if="quizResult.results[field.key]">
                                <strong class="question-label">{{ field.label }}</strong>
                                <div :style="{ color: quizResult.results[field.key].correct ? '#4caf50' : '#f44336' }">
                                    Your answer: {{ quizResult.results[field.key].userAnswer }}
                                    <span v-if="!quizResult.results[field.key].correct"> (Correct: {{ quizResult.results[field.key].correctAnswer }})</span>
                                    <span v-if="quizResult.results[field.key].points > 0" class="points-badge" style="float:right;">+{{ quizResult.results[field.key].points }} pts</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button @click="resetForm" class="btn-secondary" style="margin-top:20px;">Submit Another</button>
                </div>
            </div>
        </div>
    `,
    setup(props) {
        const form = ref(null);
        const loading = ref(true);
        const error = ref(null);
        const submitting = ref(false);
        const showResults = ref(false);
        const quizResult = ref(null);
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

                const result = await res.json();

                if (res.ok) {
                    if (result.score !== undefined) {
                        quizResult.value = result;
                    }
                    showResults.value = true;
                } else {
                    alert(result.error || 'Submission failed. Please try again.');
                }
            } catch (e) {
                alert('Network error.');
            } finally {
                submitting.value = false;
            }
        };

        const resetForm = () => {
            showResults.value = false;
            quizResult.value = null;
            // Reset data
            if (form.value && form.value.schema) {
                form.value.schema.forEach(field => {
                    formData[field.key] = field.type === 'checkbox' ? false : '';
                });
            }
        };

        onMounted(fetchForm);

        return { form, loading, error, showResults, quizResult, submitting, formData, submitForm, resetForm };
    }
};
