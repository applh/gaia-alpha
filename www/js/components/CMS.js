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
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="page in pages" :key="page.id">
                            <td>{{ page.title }}</td>
                            <td>{{ page.slug }}</td>
                            <td>{{ formatDate(page.created_at) }}</td>
                            <td class="actions">
                                <button @click="editPage(page)" class="btn-small">Edit</button>
                                <button @click="deletePage(page.id)" class="btn-small danger">Delete</button>
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
                        <label>Content</label>
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
        const form = reactive({
            id: null,
            title: '',
            slug: '',
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
            Object.assign(form, { id: null, title: '', slug: '', content: '' });
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
            if (!confirm('Are you sure you want to delete this page?')) return;

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
            pages, loading, showForm, form,
            openCreate, editPage, savePage, deletePage, cancelForm, generateSlug, formatDate
        };
    }
};
