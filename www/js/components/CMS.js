import { ref, reactive, onMounted } from 'vue';

export default {
    template: `
        <div class="cms-container">
            <div class="cms-header">
                <h2>Content Management</h2>
                <button v-if="!showForm" @click="openCreate" class="btn-primary">Create Page</button>
            </div>

            <!-- List View -->
            <div v-if="!showForm" class="cms-list">
                <div v-if="loading" class="loading">Loading pages...</div>
                <table v-else-if="pages.length">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="page in pages" :key="page.id">
                            <td>
                                <img v-if="page.image" :src="page.image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <span v-else style="color: #666; font-size: 0.8em;">No Img</span>
                            </td>
                            <td>{{ page.title }}</td>
                            <td>
                                <a :href="'/page/' + page.slug" target="_blank">{{ page.slug }}</a>
                            </td>
                            <td>{{ formatDate(page.created_at) }}</td>
                            <td class="actions">
                                <button @click="editPage(page)" class="btn-small">Edit</button>
                                <button @click="deletePage(page.id)" class="btn-small btn-danger">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="empty-state">No pages found. Create your first one!</p>
            </div>

            <!-- Form View -->
            <div v-else class="cms-form">
                <h3>{{ form.id ? 'Edit Page' : 'New Page' }}</h3>
                <form @submit.prevent="savePage">
                    <div class="form-group">
                        <label>Title</label>
                        <input v-model="form.title" required placeholder="Page Title" @input="generateSlug">
                    </div>
                    <div class="form-group">
                        <label>Slug</label>
                        <input v-model="form.slug" required placeholder="page-slug">
                    </div>
                    <div class="form-group">
                        <label>Featured Image</label>
                        <div class="featured-image-control">
                            <div v-if="form.image" class="image-preview">
                                <img :src="form.image" alt="Featured">
                                <button type="button" @click="form.image = ''" class="btn-xs btn-danger remove-btn">×</button>
                            </div>
                            <div v-else class="upload-placeholder" @click="$refs.featuredInput.click()">
                                <span>+ Upload Cover Image</span>
                            </div>
                            <input type="hidden" v-model="form.image">
                            <input type="file" ref="featuredInput" @change="uploadFeatured" style="display: none" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <div class="editor-toolbar">
                             <input type="file" ref="fileInput" @change="uploadImage" style="display: none" accept="image/jpeg,image/png,image/webp">
                             <button type="button" @click="triggerUpload" class="btn-small">Insert Image in Content</button>
                        </div>
                        <textarea v-model="form.content" rows="10" placeholder="Page content (HTML allowed)"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit">{{ form.id ? 'Update' : 'Create' }}</button>
                        <button type="button" @click="cancelForm" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    `,
    setup() {
        const pages = ref([]);
        const loading = ref(true);
        const showForm = ref(false);
        const fileInput = ref(null);
        const form = reactive({
            id: null,
            title: '',
            slug: '',
            title: '',
            slug: '',
            image: '',
            content: ''
        });

        const fetchPages = async () => {
            loading.value = true;
            try {
                const res = await fetch('/api/cms/pages');
                if (res.ok) {
                    pages.value = await res.json();
                }
            } finally {
                loading.value = false;
            }
        };

        const openCreate = () => {
            Object.assign(form, { id: null, title: '', slug: '', image: '', content: '' });
            showForm.value = true;
        };

        const editPage = (page) => {
            Object.assign(form, page);
            showForm.value = true;
        };

        const generateSlug = () => {
            if (!form.id) { // Only auto-generate on create
                form.slug = form.title.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
            }
        };

        const triggerUpload = () => {
            fileInput.value.click();
        };

        const uploadImage = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            try {
                const res = await fetch('/api/cms/upload', {
                    method: 'POST',
                    body: formData
                });

                if (res.ok) {
                    const data = await res.json();
                    // Append image to content
                    form.content += `\n<img src="${data.url}" alt="Uploaded Image" style="max-width: 100%; border-radius: 8px;">\n`;
                } else {
                    const err = await res.json();
                    alert(err.error || 'Upload failed');
                }
            } catch (e) {
                console.error(e);
                alert('Upload failed');
            }

            // Reset input
            event.target.value = '';
        };

        const uploadFeatured = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            try {
                const res = await fetch('/api/cms/upload', {
                    method: 'POST',
                    body: formData
                });

                if (res.ok) {
                    const data = await res.json();
                    form.image = data.url;
                } else {
                    const err = await res.json();
                    alert(err.error || 'Upload failed');
                }
            } catch (e) {
                console.error(e);
                alert('Upload failed');
            }
            event.target.value = '';
        };

        const savePage = async () => {
            const url = form.id ? `/api/cms/pages/${form.id}` : '/api/cms/pages';
            const method = form.id ? 'PATCH' : 'POST';

            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(form)
            });

            if (res.ok) {
                showForm.value = false;
                fetchPages();
            } else {
                const err = await res.json();
                alert(err.error || 'Failed to save page');
            }
        };

        const deletePage = async (id) => {

            const res = await fetch(`/api/cms/pages/${id}`, { method: 'DELETE' });
            if (res.ok) {
                fetchPages();
            }
        };

        const cancelForm = () => {
            showForm.value = false;
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString();
        };

        const getFirstImage = (content) => {
            const match = content.match(/<img[^>]+src=\\?"([^\\">]+)\\?"/);
            return match ? match[1] : null;
        };

        onMounted(fetchPages);

        return {
            pages, loading, showForm, form, fileInput,
            openCreate, editPage, savePage, deletePage, cancelForm, generateSlug, formatDate, triggerUpload, uploadImage, uploadFeatured
        };
    }
};
