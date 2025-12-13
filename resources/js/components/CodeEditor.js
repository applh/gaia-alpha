import { ref, onMounted, watch, onBeforeUnmount } from 'vue';

export default {
    props: ['modelValue', 'mode', 'theme'],
    emits: ['update:modelValue'],
    template: `
        <div ref="editorContainer" style="width: 100%; height: 600px; border: 1px solid #ccc; border-radius: 4px;"></div>
    `,
    setup(props, { emit }) {
        const editorContainer = ref(null);
        let editor = null;

        onMounted(() => {
            // Check if ace is loaded
            if (!window.ace) {
                const script = document.createElement('script');
                script.src = '/min/js/vendor/ace.min.js';
                script.onload = () => {
                    // Load mode and theme
                    const modeScript = document.createElement('script');
                    modeScript.src = '/min/js/vendor/ace-mode-php.min.js';
                    document.head.appendChild(modeScript);

                    const themeScript = document.createElement('script');
                    themeScript.src = '/min/js/vendor/ace-theme-monokai.min.js';
                    document.head.appendChild(themeScript);

                    const langToolsScript = document.createElement('script');
                    langToolsScript.src = '/min/js/vendor/ace-ext-language_tools.min.js';
                    langToolsScript.onload = initEditor;
                    document.head.appendChild(langToolsScript);
                };
                document.head.appendChild(script);
            } else {
                initEditor();
            }
        });

        const initEditor = () => {
            if (!editorContainer.value) return;

            // Configure Ace to use local worker files
            window.ace.config.set('basePath', '/min/js/vendor');
            window.ace.config.set('modePath', '/min/js/vendor');
            window.ace.config.set('themePath', '/min/js/vendor');
            window.ace.config.set('workerPath', '/min/js/vendor');

            editor = window.ace.edit(editorContainer.value);
            editor.setTheme("ace/theme/" + (props.theme || 'monokai'));
            editor.session.setMode("ace/mode/" + (props.mode || 'php'));
            editor.setValue(props.modelValue || '', -1); // -1 moves cursor to start

            editor.setOptions({
                fontSize: "14px",
                showPrintMargin: false,
                showGutter: true,
                highlightActiveLine: true,
                enableBasicAutocompletion: true,
                enableLiveAutocompletion: true,
                enableSnippets: true
            });

            editor.on('change', () => {
                const val = editor.getValue();
                emit('update:modelValue', val);
            });
        };

        watch(() => props.modelValue, (newVal) => {
            if (editor && newVal !== editor.getValue()) {
                editor.setValue(newVal || '', -1);
            }
        });

        watch(() => props.mode, (newMode) => {
            if (editor) editor.session.setMode("ace/mode/" + newMode);
        });

        onBeforeUnmount(() => {
            if (editor) {
                editor.destroy();
                editor.container.remove();
            }
        });

        return { editorContainer };
    }
};
