import { ref, reactive, onMounted, computed, watch } from 'vue';
import SortTh from 'ui/SortTh.js';
import TemplateBuilder from 'builders/TemplateBuilder.js';
import ComponentBuilder from 'plugins/ComponentBuilder/ComponentBuilder.js';
import SlotEditor from 'builders/SlotEditor.js';
import MenuBuilder from 'builders/MenuBuilder.js';
import Icon from 'ui/Icon.js';
import ImageSelector from 'ui/ImageSelector.js';
import CodeEditor from 'ui/CodeEditor.js';
import { useSorting } from 'composables/useSorting.js';
import { store } from 'store';

export default {
    components: { SortTh, TemplateBuilder, ComponentBuilder, MenuBuilder, LucideIcon: Icon, SlotEditor, ImageSelector, CodeEditor },
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
                         <button v-if="filterCat === 'menu'" @click="$refs.menuBuilder.openCreate()" class="btn-primary">
                            <LucideIcon name="plus" size="18" style="vertical-align:middle; margin-right:4px;"></LucideIcon> Create
                         </button>
                         <button v-if="!showForm && filterCat === 'component'" @click="openCreate" class="btn-primary">
                            <LucideIcon name="plus" size="18" style="vertical-align:middle; margin-right:4px;"></LucideIcon> Create
                         </button>
                    </div>
                </div>
                <div class="nav-tabs">
                    <button @click="filterCat = 'page'; fetchPages()" :class="{ active: filterCat === 'page' }">Pages</button>
                    <button @click="filterCat = 'template'; fetchPages()" :class="{ active: filterCat === 'template' }">Templates</button>
                    <button @click="filterCat = 'image'; fetchPages()" :class="{ active: filterCat === 'image' }">Images</button>
                    <button @click="filterCat = 'menu'" :class="{ active: filterCat === 'menu' }">Menus</button>
                    <button @click="filterCat = 'component'; fetchPages()" :class="{ active: filterCat === 'component' }">Components</button>
                </div>
                <button v-if="!showForm && filterCat === 'page'" @click="openSelector('header')" class="btn-secondary" style="margin-left: 20px;">
                    <LucideIcon name="image" size="18" style="vertical-align:middle;"></LucideIcon> Header BG
                </button>
            </div>
            
            <div class="admin-card" v-if="filterCat === 'menu'">
                <MenuBuilder ref="menuBuilder" />
            </div>

            <div class="admin-full-height" v-else-if="filterCat === 'component' && showForm" style="height: calc(100vh - 100px); margin: -20px;">
                <ComponentBuilder :initial-id="form.id" @back="showForm = false; fetchPages()" />
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
                            <SortTh v-if="filterCat === 'component'" name="name" label="ID" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                            <SortTh name="created_at" label="Created" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="page in sortedPages" :key="page.id">
                            <td>
                                <img v-if="page.image" :src="page.image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <div v-else-if="page.icon" style="width: 50px; height: 50px; background: #333; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                    <i :class="'icon-' + page.icon" style="font-size: 24px; color: #888;"></i>
                                </div>
                                <span v-else style="color: #666; font-size: 0.8em;">No Img</span>
                            </td>
                            <td>{{ page.title || page.name }}</td>
                            <td v-if="filterCat === 'page'">
                                <a :href="'/page/' + page.slug" target="_blank">{{ page.slug }}</a>
                            </td>
                            <td v-else-if="filterCat === 'template'">{{ page.slug }}</td>
                            <td v-else-if="filterCat === 'component'">
                                {{ page.name }} <br>
                                <a :href="'/component-preview/' + page.view_name" target="_blank" style="font-size: 0.8em; color: var(--accent-color);">
                                    <LucideIcon name="external-link" size="12"></LucideIcon> App View
                                </a>
                            </td>
                            <td>{{ formatDate(page.created_at) }}</td>
                            <td class="actions">
                                <button v-if="filterCat === 'page' || filterCat === 'template'" @click="editPage(page)" class="btn-small">Edit</button>
                                <button v-if="filterCat === 'component'" @click="editPage(page)" class="btn-small">Builder</button>
                                <button v-if="filterCat === 'component'" @click="deleteComponent(page.id)" class="btn-small btn-danger">Delete</button>
                                <button v-else @click="deletePage(page.id)" class="btn-small btn-danger">Delete</button>
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
                    <template v-if="filterCat === 'template'">
                        <div class="form-group">
                            <label>
                                Template Editor 
                                <span class="btn-group" style="margin-left: 10px;">
                                    <button type="button" @click="templateMode = 'visual'" class="btn-xs" :class="{ 'btn-primary': templateMode === 'visual' }">Visual Builder</button>
                                    <button type="button" @click="templateMode = 'code'" class="btn-xs" :class="{ 'btn-primary': templateMode === 'code' }">Code Editor</button>
                                </span>
                            </label>
                            
                            <div v-if="templateMode === 'visual'">
                                <div class="editor-note" style="font-size: 0.8em; color: #666; margin-bottom: 5px;">
                                    Drag and drop components to build the layout. Page content will be injected into the Main section.
                                </div>
                                <TemplateBuilder v-model="form.content" />
                            </div>

                            <div v-else class="code-editor-layout" style="display:flex; height:600px; gap:10px;">
                                <!-- File Explorer Sidebar -->
                                <div class="file-explorer" style="width:250px; background:#1e1e1e; color:#ccc; border:1px solid #333; display:flex; flex-direction:column;">
                                    <div style="padding:10px; border-bottom:1px solid #333; font-weight:bold; font-size:0.9em; text-transform:uppercase;">Files</div>
                                    <div class="file-list" style="flex:1; overflow-y:auto;">
                                        <!-- Main File -->
                                        <div @click="switchFile('main')" 
                                             :class="{ active: currentFile === 'main' }"
                                             style="padding:8px 10px; cursor:pointer; border-left:3px solid transparent;"
                                             :style="currentFile === 'main' ? 'background:#333; border-left-color:var(--primary-color); color:#fff' : ''">
                                            <LucideIcon name="file" size="14" style="margin-right:5px; vertical-align:middle;"></LucideIcon>
                                            Main Template
                                        </div>
                                        
                                        <!-- Partials Header -->
                                        <div style="padding:10px; border-top:1px solid #333; font-size:0.85em; color:#888; display:flex; justify-content:space-between; align-items:center;">
                                            <span>PARTIALS</span>
                                            <button type="button" @click="createPartial" class="btn-xs" style="background:#333; border:none; color:#fff;">+</button>
                                        </div>

                                        <!-- Partial List -->
                                        <div v-for="partial in partials" :key="partial.id"
                                             @click="switchFile('partial', partial)"
                                             :class="{ active: currentFile === 'partial' && currentPartial?.id === partial.id }"
                                             style="padding:8px 10px 8px 15px; cursor:pointer; font-size:0.9em; border-left:3px solid transparent; display:flex; justify-content:space-between; group"
                                             :style="(currentFile === 'partial' && currentPartial?.id === partial.id) ? 'background:#333; border-left-color:var(--accent-color); color:#fff' : ''">
                                            <span>
                                                <LucideIcon name="puzzle" size="14" style="margin-right:5px; vertical-align:middle;"></LucideIcon>
                                                {{ partial.name }}
                                            </span>
                                            <span v-if="currentFile === 'partial' && currentPartial?.id === partial.id" @click.stop="deletePartial(partial.id)" style="color:#f55;">×</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Editor Area -->
                                <div class="editor-area" style="flex:1; display:flex; flex-direction:column;">
                                    <div class="editor-toolbar" style="background:#1e1e1e; padding:5px 10px; border:1px solid #333; border-bottom:none; color:#888; font-size:0.8em; display:flex; justify-content:space-between; align-items:center;">
                                        <div>
                                            <span v-if="currentFile === 'main'">Editing: <strong>{{ form.title || 'Main Template' }}</strong></span>
                                            <span v-else>Editing Partial: <strong>{{ currentPartial.name }}</strong> (Usage: <code>Part::in('{{ currentPartial.name }}')</code>)</span>
                                        </div>
                                        <button type="button" @click="showTips = !showTips" style="background:none; border:none; color:#888; cursor:pointer;" title="Toggle Tips">
                                            <LucideIcon name="info" size="14"></LucideIcon> Tips
                                        </button>
                                    </div>
                                    <div v-if="showTips" style="background:#252526; padding:10px; border:1px solid #333; border-bottom:none; color:#ccc; font-size:0.85em;">
                                        <strong>Developer Quick Reference:</strong>
                                        <ul style="margin:5px 0 0 20px; padding:0; color:#aaa;">
                                            <li><code>$page['content']</code> : Output the page's main content.</li>
                                            <li><code>$page['title']</code> : Current page title.</li>
                                            <li><code>Part::in('name')</code> : Include a partial (e.g. header/footer).</li>
                                            <li><code>Page::find(id)</code> : Fetch a specific page.</li>
                                        </ul>
                                    </div>
                                    <CodeEditor v-model="activeContent" mode="php" theme="monokai" style="flex:1; height:auto;" />
                                </div>
                            </div>
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
                            <label>
                                Content 
                                <span style="float: right; font-weight: normal; font-size: 0.8em; display: flex; align-items: center; gap: 5px;">
                                    Format:
                                    <select v-model="form.content_format" style="padding: 2px 5px; background: #333; color: white; border: 1px solid #444; border-radius: 4px;">
                                        <option value="html">HTML</option>
                                        <option value="markdown">Markdown</option>
                                    </select>
                                    <button type="button" @click="useBuilder = true" class="btn-xs" v-if="isStructured">Switch to Visual</button>
                                </span>
                            </label>
                            <div class="editor-toolbar">
                                 <button type="button" @click="openSelector('content')" class="btn-small">Insert Image in Content</button>
                            </div>
                            <textarea v-model="form.content" rows="10" :placeholder="form.content_format === 'markdown' ? 'Write in Markdown...' : 'Page content (HTML allowed)'"></textarea>
                        </div>
                        <div class="form-group" v-if="filterCat === 'page'">
                            <label>Canonical URL (Optional Override)</label>
                            <input v-model="form.canonical_url" placeholder="https://example.com/original-page">
                        </div>
                        <div class="form-group" v-if="filterCat === 'page'">
                            <label>Structured Data (Schema.org)</label>
                            <div style="display:flex; gap:10px; margin-bottom:10px;">
                                <select v-model="form.schema_type" style="flex:1;">
                                    <option value="WebPage">WebPage (General)</option>
                                    <option value="Article">Article (Blog Post/News)</option>
                                    <option value="Product">Product</option>
                                    <option value="Organization">Organization</option>
                                    <option value="LocalBusiness">LocalBusiness</option>
                                </select>
                            </div>
                            <textarea v-model="form.schema_data" rows="4" placeholder='Custom JSON-LD properties (e.g. {"price": "99.99"})'></textarea>
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
        const templateMode = ref('code'); // 'code' or 'visual'
        const showTips = ref(false);
        const currentFile = ref('main'); // 'main' or 'partial'
        const partials = ref([]);
        const currentPartial = ref(null);

        const { sortColumn, sortDirection, sortBy, sortedData: sortedPages } = useSorting(pages, 'created_at', 'desc', {
            title: (row) => row.title || row.filename
        });

        const pageTitle = computed(() => {
            switch (filterCat.value) {
                case 'template': return 'Templates';
                case 'image': return 'Media Library';
                case 'menu': return 'Menu Builder';
                case 'component': return 'Component Builder';
                default: return 'Content Management';
            }
        });

        const pageIcon = computed(() => {
            switch (filterCat.value) {
                case 'template': return 'layout-template';
                case 'image': return 'image';
                case 'menu': return 'list';
                case 'component': return 'box';
                default: return 'file-text';
            }
        });



        const form = reactive({
            id: null,
            title: '',
            slug: '',
            image: '',
            content: '',
            content_format: 'html',
            cat: 'page',
            template_slug: '',
            canonical_url: '',
            schema_type: 'WebPage',
            schema_data: ''
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

        // Removed auto-copy logic as Templates are now PHP wrappers, not content blueprints.
        /* 
        watch(() => form.template_slug, async (newSlug) => {
             // ... (removed)
        });
        */



        // Computed Property for Code Editor Binding
        const activeContent = computed({
            get: () => {
                if (currentFile.value === 'main') return form.content;
                return currentPartial.value ? currentPartial.value.content : '';
            },
            set: (val) => {
                if (currentFile.value === 'main') {
                    form.content = val;
                } else if (currentFile.value === 'partial' && currentPartial.value) {
                    currentPartial.value.content = val;
                    // Auto-save partial? Or save on blur? 
                    // Let's implement debounce save or explicit save.
                    // Ideally we should have a "Save Partial" button or save with main Save.
                    // For simplicity, let's auto-save to backend on debounce or just keep in memory until...?
                    // Wait, partials are separate entities in DB. They should probably be saved independently.
                    // Or we piggyback on savePage? No, activeContent setter updates the local object.
                }
            }
        });

        const fetchPartials = async () => {
            try {
                const res = await fetch('/@/cms/partials');
                if (res.ok) partials.value = await res.json();
            } catch (e) { console.error(e); }
        };

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
                } else if (filterCat.value === 'component') {
                    url = '/@/admin/component-builder/list';
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
            Object.assign(form, { id: null, title: '', slug: '', image: '', content: '', content_format: 'html', cat: 'page', template_slug: '', canonical_url: '', schema_type: 'WebPage', schema_data: '' });
            if (filterCat.value === 'page') {
                await fetchTemplatesList();
            }
            if (filterCat.value === 'template') {
                await fetchPartials();
                currentFile.value = 'main';
            }
            // Component just needs empty form, already done above
            showForm.value = true;
        };

        const editPage = async (page) => {
            Object.assign(form, page);
            if (!form.content_format) form.content_format = 'html';
            if (!form.template_slug) form.template_slug = '';
            if (!form.canonical_url) form.canonical_url = '';
            if (!form.schema_type) form.schema_type = 'WebPage';
            if (!form.schema_data) form.schema_data = '';

            // Infer Template Mode
            if (filterCat.value === 'template') {
                await fetchPartials();
                currentFile.value = 'main';
                if (form.content && form.content.trim().startsWith('{')) {
                    templateMode.value = 'visual';
                } else {
                    templateMode.value = 'code';
                }
            }

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
                // alert('Image Selected: ' + img.image);
                console.log('Image Selected', img.image);
            }
        };

        const savePage = async () => {
            // If editing a partial, save IT instead of the main page
            if (currentFile.value === 'partial' && currentPartial.value) {
                const p = currentPartial.value;
                const url = p.id ? `/@/cms/partials/${p.id}` : '/@/cms/partials';
                const method = p.id ? 'PATCH' : 'POST';
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: p.name, content: p.content })
                });
                if (!p.id) { // New partial
                    const data = await res.json();
                    p.id = data.id;
                }
                // alert('Partial Saved!');
                console.log('Partial Saved');
                fetchPartials(); // Refresh
                return;
            }

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
                console.error(err.error || 'Failed to save page');
            }
        };

        const switchFile = (fileType, partial = null) => {
            currentFile.value = fileType;
            if (fileType === 'partial') currentPartial.value = partial;
            else currentPartial.value = null;
        };

        const createPartial = async () => {
            const name = prompt("Enter partial name (e.g. header_v2):");
            if (!name) return;
            // Optimistic add to list, then user saves content? 
            // Better to create record immediately to get ID.
            const res = await fetch('/@/cms/partials', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, content: '<!-- New Partial -->' })
            });
            if (res.ok) {
                await fetchPartials();
                // Switch to it
                const newP = partials.value.find(p => p.name === name);
                if (newP) switchFile('partial', newP);
            } else {
                console.error('Error creating partial (name taken?)');
            }
        };

        const deletePartial = async (id) => {
            // if (!confirm('Delete this partial?')) return;
            await fetch(`/@/cms/partials/${id}`, { method: 'DELETE' });
            await fetchPartials();
            if (currentFile.value === 'partial' && currentPartial.value?.id === id) {
                switchFile('main');
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

        const deleteComponent = async (id) => {
            if (!confirm('Are you sure you want to delete this component?')) return;
            const res = await fetch(`/@/admin/component-builder/${id}`, { method: 'DELETE' });
            if (res.ok) {
                fetchPages();
            } else {
                alert('Failed to delete component');
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
            } else if (val === 'cms-components') {
                filterCat.value = 'component';
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
            openCreate, editPage, savePage, deletePage, deleteComponent, cancelForm, generateSlug,
            formatDate, handleImageSelection, openSelector, fetchPages,
            sortBy, sortColumn, sortDirection, sortedPages, pageTitle, pageIcon, useBuilder, isStructured, editStructure,
            templateMode, currentFile, partials, currentPartial, activeContent, switchFile, createPartial, deletePartial, showTips
        };
    }
};
