
import { ref, reactive, onMounted, watch } from 'vue';

export default {
    props: ['formId'],
    emits: ['close'],
    template: `
        <div class="admin-page form-builder">
            <div class="admin-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button @click="$emit('close')">← Back</button>
                    <h2 class="page-title">{{ form.id ? 'Edit ' + capitalize(form.type) : 'New Form' }}</h2>
                </div>
                <div class="actions">
                    <button @click="saveForm" class="btn-primary">Save {{ capitalize(form.type) }}</button>
                </div>
            </div>

            <div class="builder-layout">
                <!-- Toolbox -->
                <div class="toolbox admin-card">
                    <h3>Toolbox</h3>
                    <p class="hint">Drag fields to the canvas</p>
                    <div class="tools-list">
                        <div 
                            v-for="type in fieldTypes" 
                            :key="type.type" 
                            class="tool-item" 
                            draggable="true" 
                            @dragstart="onDragStart($event, type)"
                        >
                            {{ type.label }}
                        </div>
                    </div>
                    
                    <hr>
                    <h3>Settings</h3>
                     <div class="prop-group">
                        <label>Mode:</label>
                        <select v-model="form.type">
                            <option value="form">Standard Form</option>
                            <option value="quiz">Quiz (Graded)</option>
                            <option value="poll">Poll</option>
                        </select>
                    </div>
                </div>

                <!-- Canvas -->
                <div 
                    class="canvas admin-card" 
                    @dragover.prevent 
                    @drop="onDrop"
                >
                    <div class="form-meta">
                        <label>Title:</label>
                        <input v-model="form.title" placeholder="My Awesome Form">
                        <label>Description:</label>
                        <input v-model="form.description" placeholder="Optional description">
                        <label>Submit Button Text:</label>
                        <input v-model="form.submit_label" placeholder="Submit">
                    </div>
                    
                    <hr>

                    <div v-if="form.schema.length === 0" class="empty-state">
                        Drop fields here to build your {{ form.type }}
                    </div>

                    <div 
                        v-for="(field, index) in form.schema" 
                        :key="field.key" 
                        class="field-item" 
                        :class="{ selected: selectedField === field }"
                        @click="selectedField = field"
                    >
                        <div class="field-preview">
                            <label>
                                {{ field.label }} 
                                <span v-if="field.required" style="color:red">*</span>
                                <span v-if="form.type === 'quiz' && field.points" class="badge-points">{{ field.points }} pts</span>
                            </label>
                            
                            <input v-if="['text','email','number'].includes(field.type)" :type="field.type" disabled :placeholder="field.placeholder">
                            <textarea v-if="field.type === 'textarea'" disabled :placeholder="field.placeholder" :rows="field.rows || 3"></textarea>
                            <select v-if="field.type === 'select'" disabled><option>Select option...</option></select>
                            <div v-if="field.type === 'radio'">
                                <div v-for="opt in (field.options || ['Option 1', 'Option 2'])">
                                    <input type="radio" disabled> {{ opt }}
                                </div>
                            </div>
                            <div v-if="field.type === 'checkbox'">
                                <input type="checkbox" disabled> {{ field.placeholder }}
                            </div>
                        </div>
                        <div class="field-actions">
                            <button @click.stop="moveField(index, -1)" :disabled="index === 0">↑</button>
                            <button @click.stop="moveField(index, 1)" :disabled="index === form.schema.length - 1">↓</button>
                            <button @click.stop="removeField(index)" class="danger">×</button>
                        </div>
                    </div>
                </div>

                <!-- Properties -->
                <div v-if="selectedField" class="properties admin-card">
                    <h3>Properties</h3>
                    <div class="prop-group">
                        <label>Type:</label>
                        <select v-model="selectedField.type">
                            <option v-for="type in fieldTypes" :key="type.type" :value="type.type">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>
                    <div class="prop-group">
                        <label>Label:</label>
                        <input v-model="selectedField.label">
                    </div>
                    
                    <!-- Advanced Quiz Properties -->
                    <template v-if="form.type === 'quiz'">
                         <div class="prop-group">
                            <label>Points:</label>
                            <input type="number" v-model.number="selectedField.points">
                        </div>
                         <div class="prop-group">
                            <label>Correct Answer:</label>
                             <input v-if="['text','number','email'].includes(selectedField.type)" v-model="selectedField.correctAnswer" placeholder="Exact match">
                             <select v-if="selectedField.type === 'select' || selectedField.type === 'radio'" v-model="selectedField.correctAnswer">
                                 <option v-for="opt in selectedField.options" :key="opt" :value="opt">{{ opt }}</option>
                             </select>
                             <div v-if="selectedField.type === 'checkbox'">
                                 <!-- Single Checkbox = Boolean or Value? Usually boolean true -->
                                 <label><input type="checkbox" v-model="selectedField.correctAnswer"> Checked is correct</label>
                             </div>
                        </div>
                    </template>

                    <div class="prop-group" v-if="selectedField.type === 'textarea'">
                        <label>Rows:</label>
                        <input type="number" v-model.number="selectedField.rows">
                    </div>
                    <div class="prop-group" v-if="selectedField.type !== 'checkbox'">
                        <label>Placeholder:</label>
                        <input v-model="selectedField.placeholder">
                    </div>
                    <div class="prop-group" v-if="selectedField.type === 'checkbox'">
                        <label>Checkbox Text:</label>
                        <input v-model="selectedField.placeholder">
                    </div>
                    <div class="prop-group">
                        <label>
                            <input type="checkbox" v-model="selectedField.required"> Required
                        </label>
                    </div>
                    
                    <div v-if="['select', 'radio'].includes(selectedField.type)">
                        <label>Options (comma separated):</label>
                        <textarea v-model="selectedField.optionsText" @change="updateOptions(selectedField)"></textarea>
                    </div>
                </div>
                <div v-else class="properties admin-card">
                    <p class="hint">Select a field to edit properties</p>
                </div>
            </div>
        </div>
    `,
    setup(props, { emit }) {
        const form = reactive({
            id: null,
            title: '',
            description: '',
            submit_label: 'Submit',
            type: 'form',
            settings: {},
            schema: [] // Array of fields
        });

        const selectedField = ref(null);

        const fieldTypes = [
            { type: 'text', label: 'Text Input' },
            { type: 'textarea', label: 'Text Area' },
            { type: 'number', label: 'Number' },
            { type: 'email', label: 'Email' },
            { type: 'select', label: 'Dropdown' },
            { type: 'radio', label: 'Radio Buttons' },
            { type: 'checkbox', label: 'Checkbox' }
        ];

        const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1);

        const onDragStart = (evt, type) => {
            evt.dataTransfer.dropEffect = 'copy';
            evt.dataTransfer.effectAllowed = 'copy';
            evt.dataTransfer.setData('type', type.type);
        };

        const onDrop = (evt) => {
            const type = evt.dataTransfer.getData('type');
            if (type) {
                addField(type);
            }
        };

        const addField = (type) => {
            const field = {
                key: 'field_' + Date.now() + Math.random().toString(36).substr(2, 5),
                type: type,
                label: 'New ' + type,
                placeholder: '',
                required: false,
                options: [],
                points: 0,
                correctAnswer: null
            };
            if (type === 'select' || type === 'radio') field.optionsText = '';
            if (type === 'textarea') field.rows = 3;

            form.schema.push(field);
            selectedField.value = field;
        };

        const removeField = (index) => {
            if (selectedField.value === form.schema[index]) {
                selectedField.value = null;
            }
            form.schema.splice(index, 1);
        };

        const moveField = (index, offset) => {
            const newIndex = index + offset;
            if (newIndex >= 0 && newIndex < form.schema.length) {
                const item = form.schema.splice(index, 1)[0];
                form.schema.splice(newIndex, 0, item);
            }
        };

        const updateOptions = (field) => {
            if (field.optionsText) {
                field.options = field.optionsText.split(',').map(s => s.trim()).filter(s => s);
            } else {
                field.options = [];
            }
        };

        const saveForm = async () => {
            if (!form.title) {
                alert('Title is required');
                return;
            }

            const url = form.id ? `/@/forms/${form.id}` : '/@/forms';
            const method = form.id ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(form)
                });
                const data = await res.json();

                if (res.ok) {
                    emit('close');
                } else {
                    alert(data.error || 'Save failed');
                }
            } catch (e) {
                alert('Network error');
            }
        };

        onMounted(async () => {
            if (props.formId) {
                const res = await fetch(`/@/forms/${props.formId}`);
                if (res.ok) {
                    const data = await res.json();
                    Object.assign(form, data);
                    // Process optionsText for select/radio fields
                    form.schema.forEach(f => {
                        if ((f.type === 'select' || f.type === 'radio') && f.options) {
                            f.optionsText = f.options.join(', ');
                        }
                    });
                }
            }
        });

        return { form, fieldTypes, selectedField, onDragStart, onDrop, removeField, moveField, updateOptions, saveForm, capitalize };
    }
};
