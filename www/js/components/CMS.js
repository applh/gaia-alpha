import { ref, reactive, onMounted } from 'vue';

export default {
    template: `
        <div class="cms-container">
            <div class="cms-header">
                <h2>Content Management</h2>
                <div class="header-actions">
                    <select v-model="filterCat" @change="fetchPages">
                        <option value="page">Pages</option>
                        <option value="image">Images</option>
                    </select>
                    <button v-if="!showForm && filterCat === 'page'" @click="openCreate" class="btn-primary">Create Page</button>
                    <!-- Images are uploaded via inline or drag drop usually, but maybe we want a dedicated upload button here too? -->
                    <!-- For now, requirement says "member cms will only list rows with cat 'page'". 
                         Actually "member cms will only list rows with cat 'page' (by default I assume? or strictly?)"
                         "add a filter to select cat possible values" -> implies we can see others.
                    -->
                    <button v-if="!showForm && filterCat === 'image'" @click="$refs.headerUpload.click()" class="btn-secondary">Upload Image</button>
                    <input type="file" ref="headerUpload" @change="uploadHeaderImage" style="display: none" accept="image/*">
                </div>
            </div>

            <!-- List View -->
            <div v-if="!showForm" class="cms-list">
                <div v-if="loading" class="loading">Loading {{ filterCat }}s...</div>
                <table v-else-if="pages.length">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>{{ filterCat === 'image' ? 'Filename' : 'Title' }}</th>
                            <th v-if="filterCat === 'page'">Slug</th>
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
                            <td v-if="filterCat === 'page'">
                                <a :href="'/page/' + page.slug" target="_blank">{{ page.slug }}</a>
                            </td>
                            <td>{{ formatDate(page.created_at) }}</td>
                            <td class="actions">
                                <button v-if="filterCat === 'page'" @click="editPage(page)" class="btn-small">Edit</button>
                                <button @click="deletePage(page.id)" class="btn-small btn-danger">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="empty-state">No {{ filterCat }}s found.</p>
            </div>

            <!-- Form View (Only for Pages) -->
            <div v-if="showForm" class="cms-form">
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
        const headerUpload = ref(null);
        const filterCat = ref('page');

        const form = reactive({
            id: null,
            title: '',
            slug: '',
            image: '',
            content: '',
            cat: 'page'
        });

        const fetchPages = async () => {
            loading.value = true;
            try {
                const res = await fetch(`/api/cms/pages?cat=${filterCat.value}`);
                if (res.ok) {
                    pages.value = await res.json();
                }
            } finally {
                loading.value = false;
            }
        };

        const openCreate = () => {
            Object.assign(form, { id: null, title: '', slug: '', image: '', content: '', cat: 'page' });
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
                const res = await fetch('/api/cms/upload', { method: 'POST', body: formData });

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

        const uploadHeaderImage = async (event) => {
            const file = event.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('image', file);
            try {
                const res = await fetch('/api/cms/upload', { method: 'POST', body: formData });
                if (res.ok) {
                    fetchPages(); // Refresh list to see new image
                } else {
                    alert('Upload failed');
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

        onMounted(fetchPages);

        return {
            pages, loading, showForm, form, fileInput, headerUpload, filterCat,
            openCreate, editPage, savePage, deletePage, cancelForm, generateSlug,
            formatDate, triggerUpload, uploadImage, uploadFeatured, uploadHeaderImage, fetchPages
        };
    }
};
