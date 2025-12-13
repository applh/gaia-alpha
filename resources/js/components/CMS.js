import { ref, reactive, onMounted, computed, watch } from 'vue';
import SortTh from './SortTh.js';
import TemplateBuilder from './TemplateBuilder.js';
import SlotEditor from './SlotEditor.js';
import MenuBuilder from './MenuBuilder.js';
import Icon from './Icon.js';
import ImageSelector from './ImageSelector.js';
import { useSorting } from '../composables/useSorting.js';
import { store } from '../store.js';

export default {
    components: { SortTh, TemplateBuilder, MenuBuilder, LucideIcon: Icon, SlotEditor, ImageSelector },
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
                <button v-if="!showForm && filterCat === 'page'" @click="openSelector('header')" class="btn-secondary" style="margin-left: 20px;">
                    <LucideIcon name="image" size="18" style="vertical-align:middle;"></LucideIcon> Header BG
                </button>
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
                            <div v-else class="upload-placeholder" @click="openSelector('featured')">
                                <span>+ Select Cover Image</span>
                            </div>
                            <input type="hidden" v-model="form.image">
                        </div>
                    </div>
                    </template>
                    <!-- Template Specific Fields -->
                    <template v-if="filterCat === 'template'">
                        <div class="form-group">
                            <label>Structure Builder</label>
                            <TemplateBuilder v-model="form.content" />
                        </div>
                    </template>

                    <!-- Page Editor with Visual Builder Option -->
                    <template v-if="filterCat === 'page'">
                        <div class="form-group" v-if="useBuilder">
                            <label>
                                Visual Editor 
                                <span class="btn-group" style="margin-left:10px;">
                                    <button type="button" @click="editStructure = !editStructure" class="btn-xs" :class="{ 'btn-primary': editStructure }">
                                        {{ editStructure ? 'Switch to Slots' : 'Edit Structure' }}
                                    </button>
                                    <button type="button" @click="useBuilder = false" class="btn-xs">Switch to Code</button>
                                </span>
                            </label>
                            
                            <TemplateBuilder v-if="editStructure" v-model="form.content" />
                            <SlotEditor v-else v-model="form.content" />
                        </div>
                        <div class="form-group" v-else>
                            <label>Content <button type="button" @click="useBuilder = true" class="btn-xs" v-if="isStructured">Switch to Visual</button></label>
                            <div class="editor-toolbar">
                                 <button type="button" @click="openSelector('content')" class="btn-small">Insert Image in Content</button>
                            </div>
                            <textarea v-model="form.content" rows="10" placeholder="Page content (HTML allowed)"></textarea>
                        </div>
                    </template>
                    <div class="form-actions">
                        <button type="submit">{{ form.id ? 'Update' : 'Create' }}</button>
                        <button type="button" @click="cancelForm" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <ImageSelector 
            :show="showImageSelector" 
            @close="showImageSelector = false"
            @select="handleImageSelection"
        />

    </div>
    `,
    setup() {
        const pages = ref([]);
        const allTemplates = ref([]);
        const loading = ref(true);
        const showForm = ref(false);
        const showImageSelector = ref(false);
        const selectorMode = ref('featured'); // featured, content, header
        const filterCat = ref('page');
        const useBuilder = ref(false);
        const editStructure = ref(false);
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

        const isStructured = computed(() => {
            try {
                const parsed = JSON.parse(form.content);
                return parsed && (parsed.header || parsed.main || parsed.footer);
            } catch (e) {
                return false;
            }
        });

        watch(() => form.content, (val) => {
            // Auto-detect JSON structure for existing pages
            if (!useBuilder.value && val && val.trim().startsWith('{')) {
                if (isStructured.value) useBuilder.value = true;
            }
        }, { immediate: true });

        watch(() => form.template_slug, async (newSlug) => {
            if (newSlug) {
                // For new pages or explicit changes, automatic apply if content allows
                // If content is empty or short, we apply.
                // Or if user just switched template, we assume they want that structure.
                // Since user explicitly selected dropdown, we do it.
                if (!form.content || !form.id || confirm('Switching template will overwrite current content. Ok?')) {
                    const template = allTemplates.value.find(t => t.slug === newSlug);
                    if (template) {
                        // Implicitly apply without annoying popup if content is empty or it's a new page creation flow
                        // effectively removing the "flash" for the happy path
                        if (!form.id || !form.content) {
                            form.content = template.content;
                            useBuilder.value = true;
                        } else {
                            // Only ask if there is potentially valuable content being lost
                            // But user asked to remove "flashing popup"
                            // Let's assume on creation (no ID) we just do it.
                            form.content = template.content;
                            useBuilder.value = true;
                        }
                    }
                }
            }
        });

        const fetchTemplatesList = async () => {
            try {
                const res = await fetch('/@/cms/templates');
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
                    url = '/@/cms/templates';
                } else {
                    url = `/@/cms/pages?cat=${filterCat.value}`;
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



        const openSelector = (mode) => {
            selectorMode.value = mode;
            showImageSelector.value = true;
        };

        const handleImageSelection = async (img) => {
            if (selectorMode.value === 'featured') {
                form.image = img.image;
            } else if (selectorMode.value === 'content') {
                form.content += `\n<img src="${img.image}" alt="${img.title || 'image'}" style="max-width: 100%; border-radius: 8px;">\n`;
            } else if (selectorMode.value === 'header') {
                // For header, we might want to just show it or save it somewhere? 
                // The original code uploaded it. Here we just log it or maybe assume it's for global settings?
                // The original code did: fetchPages() after upload. It seems it was just a quick upload tool?
                // Replicating original behavior: just "select" it (maybe user copies URL?)
                // Or maybe it was intended to update global site header. 
                // Based on "uploadHeaderImage" implementation in original (lines 360+), it just uploaded and refreshed list.
                // It didn't seemingly SAVE it to any config. So it was likely just a "Quick Upload" button.
                // So if mode is header, we just do nothing special, since upload happens in selector.
                // But selector emits 'select' after upload is done if we implement it that way.
                // The ImageSelector handles upload internal to itself. 
                // So "selecting" an image for "Header BG" when it was likely just a generic upload button seems confused in original.
                // I'll assume users want to just copy the URL or it was a way to add images to library generally?
                alert('Image Selected: ' + img.image);
            }
        };

        const savePage = async () => {
            let url;
            if (filterCat.value === 'template') {
                url = form.id ? `/@/cms/templates/${form.id}` : '/@/cms/templates';
            } else {
                url = form.id ? `/@/cms/pages/${form.id}` : '/@/cms/pages';
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
                url = `/@/cms/templates/${id}`;
            } else {
                url = `/@/cms/pages/${id}`;
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
            pages, allTemplates, loading, showForm, showImageSelector, form, filterCat,
            openCreate, editPage, savePage, deletePage, cancelForm, generateSlug,
            formatDate, handleImageSelection, openSelector, fetchPages,
            sortBy, sortColumn, sortDirection, sortedPages, pageTitle, pageIcon, useBuilder, isStructured, editStructure
        };
    }
};
