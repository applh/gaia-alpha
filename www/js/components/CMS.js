import { ref, reactive, onMounted, computed, watch } from 'vue';
import SortTh from './SortTh.js';
import TemplateBuilder from './TemplateBuilder.js';
import MenuBuilder from './MenuBuilder.js';
import Icon from './Icon.js';
import { useSorting } from '../composables/useSorting.js';
import { store } from '../store.js';

export default {
    components: { SortTh, TemplateBuilder, MenuBuilder, LucideIcon: Icon },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <div style="display:flex; align-items:center; gap:20px;">
                    <h2 class="page-title">
                        <LucideIcon :name="pageIcon" size="32" style="display:inline-block; vertical-align:middle; margin-right:6px;"></LucideIcon>
                        {{ pageTitle }}
                    </h2>
                    <div class="primary-actions" style="display:flex; gap:10px;">
                         <button v-if="!showForm && filterCat === 'page'" @click="openCreate" class="btn-primary">
                            <LucideIcon name="plus" size="18" style="vertical-align:middle; margin-right:4px;"></LucideIcon> Create
                         </button>
                         <button v-if="!showForm && filterCat === 'template'" @click="openCreate" class="btn-primary">
                            <LucideIcon name="plus" size="18" style="vertical-align:middle; margin-right:4px;"></LucideIcon> Create
                         </button>
                         <button v-if="!showForm && filterCat === 'image'" @click="$refs.headerUpload.click()" class="btn-secondary">
                            <LucideIcon name="upload" size="18" style="vertical-align:middle; margin-right:4px;"></LucideIcon> Upload
                         </button>
                    </div>
                </div>
                <div class="nav-tabs">
                    <button @click="filterCat = 'page'; fetchPages()" :class="{ active: filterCat === 'page' }">Pages</button>
                    <button @click="filterCat = 'template'; fetchPages()" :class="{ active: filterCat === 'template' }">Templates</button>
                    <button @click="filterCat = 'image'; fetchPages()" :class="{ active: filterCat === 'image' }">Images</button>
                    <button @click="filterCat = 'menu'" :class="{ active: filterCat === 'menu' }">Menus</button>
                </div>
                <input type="file" ref="headerUpload" @change="uploadHeaderImage" style="display: none" accept="image/*">
            </div>
            
            <div class="admin-card" v-if="filterCat === 'menu'">
                <MenuBuilder />
            </div>

            <div class="admin-card" v-else>

            <!-- List View -->
            <div v-if="!showForm" class="cms-list">
                <div v-if="loading" class="loading">Loading {{ filterCat }}s...</div>
                <table v-else-if="pages.length">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <SortTh name="title" :label="filterCat === 'image' ? 'Filename' : 'Title'" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                            <SortTh v-if="filterCat === 'page' || filterCat === 'template'" name="slug" label="Slug" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                            <SortTh name="created_at" label="Created" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="page in sortedPages" :key="page.id">
                            <td>
                                <img v-if="page.image" :src="page.image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <span v-else style="color: #666; font-size: 0.8em;">No Img</span>
                            </td>
                            <td>{{ page.title }}</td>
                            <td v-if="filterCat === 'page'">
                                <a :href="'/page/' + page.slug" target="_blank">{{ page.slug }}</a>
                            </td>
                            <td v-else-if="filterCat === 'template'">{{ page.slug }}</td>
                            <td>{{ formatDate(page.created_at) }}</td>
                            <td class="actions">
                                <button v-if="filterCat === 'page' || filterCat === 'template'" @click="editPage(page)" class="btn-small">Edit</button>
                                <button @click="deletePage(page.id)" class="btn-small btn-danger">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="empty-state">No {{ filterCat }}s found.</p>
            </div>

            <!-- Form View -->
            <div v-if="showForm" class="cms-form">
                <h3>{{ form.id ? 'Edit ' + (filterCat === 'template' ? 'Template' : 'Page') : 'New ' + (filterCat === 'template' ? 'Template' : 'Page') }}</h3>
                <form @submit.prevent="savePage">
                    <div class="form-group">
                        <label>Title</label>
                        <input v-model="form.title" required placeholder="Page Title" @input="generateSlug">
                    </div>
                    <div class="form-group">
                        <label>Slug</label>
                        <input v-model="form.slug" required placeholder="page-slug">
                    </div>
                    
                    <!-- Page Specific Fields -->
                    <template v-if="filterCat === 'page'">
                        <div class="form-group">
                            <label>Template</label>
                            <select v-model="form.template_slug">
                                <option value="">Default (No Template)</option>
                                <option v-for="t in allTemplates" :key="t.slug" :value="t.slug">{{ t.title }}</option>
                            </select>
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
                    </template>

                    <!-- Template Specific Fields -->
                    <template v-if="filterCat === 'template'">
                        <div class="form-group">
                            <label>Structure Builder</label>
                            <TemplateBuilder v-model="form.content" />
                        </div>
                    </template>
                    <div class="form-actions">
                        <button type="submit">{{ form.id ? 'Update' : 'Create' }}</button>
                        <button type="button" @click="cancelForm" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    `,
    setup() {
        const pages = ref([]);
        const allTemplates = ref([]);
        const loading = ref(true);
        const showForm = ref(false);
        const fileInput = ref(null);
        const headerUpload = ref(null);
        const filterCat = ref('page');
        const { sortColumn, sortDirection, sortBy, sortedData: sortedPages } = useSorting(pages, 'created_at', 'desc', {
            title: (row) => row.title || row.filename
        });

        const pageTitle = computed(() => {
            switch (filterCat.value) {
                case 'template': return 'Templates';
                case 'image': return 'Media Library';
                case 'menu': return 'Menu Builder';
                default: return 'Content Management';
            }
        });

        const pageIcon = computed(() => {
            switch (filterCat.value) {
                case 'template': return 'layout-template';
                case 'image': return 'image';
                case 'menu': return 'list';
                default: return 'file-text';
            }
        });



        const form = reactive({
            id: null,
            title: '',
            slug: '',
            image: '',
            content: '',
            cat: 'page',
            template_slug: ''
        });

        const fetchTemplatesList = async () => {
            try {
                const res = await fetch('/api/cms/templates');
                if (res.ok) {
                    allTemplates.value = await res.json();
                }
            } catch (e) {
                console.error(e);
            }
        };

        const fetchPages = async () => {
            if (filterCat.value === 'menu') return;
            loading.value = true;
            try {
                let url;
                if (filterCat.value === 'template') {
                    url = '/api/cms/templates';
                } else {
                    url = `/api/cms/pages?cat=${filterCat.value}`;
                }
                const res = await fetch(url);
                if (res.ok) {
                    pages.value = await res.json();
                }
            } finally {
                loading.value = false;
            }
        };

        const openCreate = async () => {
            Object.assign(form, { id: null, title: '', slug: '', image: '', content: '', cat: 'page', template_slug: '' });
            if (filterCat.value === 'page') {
                await fetchTemplatesList();
            }
            showForm.value = true;
        };

        const editPage = async (page) => {
            Object.assign(form, page);
            if (!form.template_slug) form.template_slug = '';
            showForm.value = true;
            if (filterCat.value === 'page') {
                await fetchTemplatesList();
            }
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
            let url;
            if (filterCat.value === 'template') {
                url = form.id ? `/api/cms/templates/${form.id}` : '/api/cms/templates';
            } else {
                url = form.id ? `/api/cms/pages/${form.id}` : '/api/cms/pages';
            }

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
            let url;
            if (filterCat.value === 'template') {
                url = `/api/cms/templates/${id}`;
            } else {
                url = `/api/cms/pages/${id}`;
            }
            const res = await fetch(url, { method: 'DELETE' });
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

        // Watch store.state.currentView to switch tabs if needed
        watch(() => store.state.currentView, (val) => {
            if (val === 'cms-templates') {
                filterCat.value = 'template';
                fetchPages();
                showForm.value = false;
            } else if (val === 'cms') {
                filterCat.value = 'page';
                fetchPages();
                showForm.value = false;
            }
        }, { immediate: true });

        onMounted(fetchPages);

        return {
            pages, allTemplates, loading, showForm, form, fileInput, headerUpload, filterCat,
            openCreate, editPage, savePage, deletePage, cancelForm, generateSlug,
            formatDate, triggerUpload, uploadImage, uploadFeatured, uploadHeaderImage, fetchPages,
            sortBy, sortColumn, sortDirection, sortedPages, pageTitle, pageIcon
        };
    }
};
