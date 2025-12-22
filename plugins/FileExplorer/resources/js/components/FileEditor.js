import { ref, watch, onMounted } from 'vue';

export default {
    name: 'FileEditor',
    props: {
        modelValue: { type: String, default: '' },
        filePath: { type: String, default: '' },
        readOnly: { type: Boolean, default: false }
    },
    emits: ['update:modelValue', 'save'],
    template: `
    <div class="file-editor">
        <div class="editor-toolbar">
            <span class="file-info">{{ filePath }}</span>
            <button v-if="!readOnly" @click="$emit('save')" class="btn btn-sm btn-primary">Save</button>
        </div>
        <textarea 
            class="editor-textarea" 
            :value="modelValue" 
            @input="$emit('update:modelValue', $event.target.value)"
            :readonly="readOnly"
            spellcheck="false"
        ></textarea>
    </div>
    `,
};
