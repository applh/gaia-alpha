import { ref, onMounted, computed, watch } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
    <div class="newsletter-builder h-full flex flex-col">
        <div class="admin-header">
             <div class="flex items-center gap-2">
                <button @click="back" class="btn btn-sm"><LucideIcon name="arrow-left" size="16" /></button>
                <h2 class="page-title">
                    {{ id === 'new' ? 'New Newsletter' : 'Edit Newsletter' }}
                </h2>
            </div>
            <div class="button-group">
                <button @click="insertImage" class="btn"><LucideIcon name="image" size="16" /> Insert Image</button>
                <button @click="save" class="btn btn-primary"><LucideIcon name="save" size="16" /> Save</button>
            </div>
        </div>
        
        <div class="editor-meta mb-4 p-4 bg-white border-b rounded">
            <input v-model="subject" placeholder="Newsletter Subject" class="w-full text-lg p-2 border rounded" />
        </div>

        <div class="editor-container flex-1 flex overflow-hidden border rounded h-[calc(100vh-200px)]">
             <!-- Markdown Editor -->
             <div class="editor-pane w-1/2 flex flex-col border-r bg-gray-50">
                <div class="pane-header p-2 bg-gray-100 font-bold text-xs uppercase text-gray-500">Markdown</div>
                <textarea 
                    v-model="markdown" 
                    class="flex-1 w-full p-4 font-mono text-sm resize-none focus:outline-none" 
                    placeholder="# Hello World..."
                ></textarea>
             </div>

             <!-- Preview -->
             <div class="preview-pane w-1/2 flex flex-col bg-white">
                <div class="pane-header p-2 bg-gray-100 font-bold text-xs uppercase text-gray-500">Preview</div>
                <div class="preview-content flex-1 p-8 overflow-y-auto prose max-w-none" v-html="renderedHtml"></div>
             </div>
        </div>

        <!-- Image Insert Modal -->
        <div v-if="showImageModal" class="modal-overlay" @click="showImageModal = false">
            <div class="modal-content" @click.stop>
                <h3>Insert Image</h3>
                <input v-model="imageUrl" placeholder="Image URL (http://...)" class="form-control mb-4" />
                <p class="text-xs text-gray-500 mb-4">Pro Tip: Upload to Media Library first, then copy the URL.</p>
                <div class="modal-footer">
                    <button @click="showImageModal = false" class="btn">Cancel</button>
                    <button @click="confirmInsertImage" class="btn btn-primary">Insert</button>
                </div>
            </div>
        </div>
    </div>
    `,
    style: `
    .editor-container { min-height: 500px; }
    .prose img { max-width: 100%; border-radius: 4px; }
    .prose h1 { font-size: 2em; font-weight: bold; margin-bottom: 0.5em; }
    .prose h2 { font-size: 1.5em; font-weight: bold; margin-bottom: 0.5em; }
    .prose p { margin-bottom: 1em; line-height: 1.6; }
    .prose ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 1em; }
    .prose a { color: #3b82f6; text-decoration: underline; }
    `,
    setup() {
        const id = ref('new');
        const subject = ref('');
        const markdown = ref('');
        const showImageModal = ref(false);
        const imageUrl = ref('');

        // Simple Markdown Parser (Regex-based)
        const parseMarkdown = (md) => {
            if (!md) return '';
            let html = md
                .replace(/^### (.*$)/gim, '<h3>$1</h3>')
                .replace(/^## (.*$)/gim, '<h2>$1</h2>')
                .replace(/^# (.*$)/gim, '<h1>$1</h1>')
                .replace(/\*\*(.*)\*\*/gim, '<b>$1</b>')
                .replace(/\*(.*)\*/gim, '<i>$1</i>')
                .replace(/!\[(.*?)\]\((.*?)\)/gim, "<img alt='$1' src='$2' />")
                .replace(/\[(.*?)\]\((.*?)\)/gim, "<a href='$2'>$1</a>")
                .replace(/\n\s*\n/g, '</p><p>') // Paragraphs
                .replace(/\n-(.*)/gim, '<ul><li>$1</li></ul>') // Lists (rough)
                .replace(/<\/ul><ul>/g, ''); // Fix adjacent lists

            return `<p>${html}</p>`;
        };

        const renderedHtml = computed(() => parseMarkdown(markdown.value));

        const getUrlParam = (name) => {
            const urlParams = new URLSearchParams(window.location.hash.split('?')[1]);
            return urlParams.get(name);
        };

        const load = async () => {
            const paramId = getUrlParam('id');
            if (paramId && paramId !== 'new') {
                id.value = paramId;
                const res = await fetch(`/@/mail/newsletters/${id.value}`);
                if (res.ok) {
                    const data = await res.json();
                    subject.value = data.subject;
                    markdown.value = data.content_md || '';
                }
            }
        };

        const save = async () => {
            const payload = {
                subject: subject.value,
                content_md: markdown.value,
                content_html: renderedHtml.value,
                status: 'draft'
            };

            let url = '/@/mail/newsletters';
            let method = 'POST';

            if (id.value !== 'new') {
                url += '/' + id.value;
                method = 'PUT';
            }

            const res = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                const data = await res.json();
                if (id.value === 'new') {
                    // Update ID and URL without reload
                    id.value = data.id;
                    window.history.replaceState(null, '', `#/mail/newsletter-editor?id=${data.id}`);
                }
                alert('Saved!');
            } else {
                alert('Error saving');
            }
        };

        const insertImage = () => {
            showImageModal.value = true;
        };

        const confirmInsertImage = () => {
            if (imageUrl.value) {
                const imgMd = `![Image](${imageUrl.value})`;
                markdown.value += `\n${imgMd}\n`;
                imageUrl.value = '';
                showImageModal.value = false;
            }
        };

        const back = () => window.location.hash = '#/mail/newsletters';

        onMounted(() => {
            load();
        });

        return {
            id, subject, markdown, renderedHtml, showImageModal, imageUrl,
            save, insertImage, confirmInsertImage, back
        };
    }
}
