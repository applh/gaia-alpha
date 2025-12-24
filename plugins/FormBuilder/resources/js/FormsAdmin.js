import { ref, onMounted } from 'vue';
import FormBuilder from './FormBuilder.js';
import FormSubmissions from './FormSubmissions.js';
import FormDashboard from './FormDashboard.js';
import DataTable from 'ui/DataTable.js';
import UIButton from 'ui/Button.js';
import Container from 'ui/Container.js';
import Card from 'ui/Card.js';
import Tag from 'ui/Tag.js';
import { useSorting } from 'composables/useSorting.js';
import Icon from 'ui/Icon.js';

export default {
    components: {
        FormBuilder,
        FormSubmissions,
        FormDashboard,
        'ui-data-table': DataTable,
        'ui-button': UIButton,
        'ui-container': Container,
        'ui-card': Card,
        'ui-tag': Tag,
        LucideIcon: Icon
    },
    template: `
        <div>
            <!-- List View -->
            <ui-container v-if="view === 'list'" class="admin-page">
                <div class="admin-header">
                    <h2 class="page-title">Form Builder</h2>
                    <ui-button type="primary" @click="openBuilder(null)">
                        <LucideIcon name="plus" size="18" style="margin-right: 8px;" />
                        Create New
                    </ui-button>
                </div>
                
                <ui-card>
                    <ui-data-table 
                        v-if="forms.length"
                        :data="sortedForms" 
                        :columns="columns"
                    >
                        <template #col-id="{ row }">#{{ row.id }}</template>
                        <template #col-title="{ row }"><strong>{{ row.title }}</strong></template>
                        <template #col-type="{ row }">
                            <ui-tag :type="row.type === 'quiz' ? 'primary' : 'info'">{{ row.type || 'form' }}</ui-tag>
                        </template>
                        <template #col-slug="{ row }">
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <a :href="getPublicLink(row.slug)" target="_blank" class="text-link">/f/{{ row.slug }}</a>
                                <ui-button size="small" @click="copyLink(row.slug)" title="Copy Link">
                                    <LucideIcon name="copy" size="14" />
                                </ui-button>
                            </div>
                        </template>
                        <template #col-created_at="{ row }">
                            {{ new Date(row.created_at).toLocaleDateString() }}
                        </template>
                        <template #col-actions="{ row }">
                            <div style="display: flex; gap: 4px;">
                                <ui-button size="small" @click="openBuilder(row.id)">Edit</ui-button>
                                <ui-button size="small" @click="openSubmissions(row.id)">Submissions</ui-button>
                                <ui-button size="small" @click="openStats(row.id)">Stats</ui-button>
                                <ui-button size="small" type="danger" @click="deleteForm(row.id)">Delete</ui-button>
                            </div>
                        </template>
                    </ui-data-table>
                    
                    <div v-else class="text-center" style="padding: 48px;">
                        <LucideIcon name="form-input" size="48" style="margin-bottom: 16px; opacity: 0.3;" />
                        <p class="text-muted" style="margin-bottom: 24px;">No forms or quizzes created yet.</p>
                        <ui-button type="primary" @click="openBuilder(null)">Create your first one</ui-button>
                    </div>
                </ui-card>
            </ui-container>

            <!-- Builder View -->
            <FormBuilder 
                v-if="view === 'builder'" 
                :formId="activeId" 
                @close="closeSubView" 
            />

            <!-- Submissions View -->
            <FormSubmissions 
                v-if="view === 'submissions'" 
                :formId="activeId" 
                @close="closeSubView" 
            />
            
             <!-- Stats View -->
            <FormDashboard 
                v-if="view === 'stats'" 
                :formId="activeId" 
                @close="closeSubView" 
            />
        </div>
    `,
    setup() {
        const view = ref('list');
        const activeId = ref(null);
        const forms = ref([]);

        const { sortColumn, sortDirection, sortBy, sortedData: sortedForms } = useSorting(forms, 'created_at', 'desc');

        const columns = [
            { label: 'ID', prop: 'id', width: '80px' },
            { label: 'Title', prop: 'title' },
            { label: 'Type', prop: 'type', width: '100px' },
            { label: 'Public Link', prop: 'slug' },
            { label: 'Created', prop: 'created_at', width: '150px' },
            { label: 'Actions', prop: 'actions', align: 'right', width: '320px' }
        ];

        const fetchForms = async () => {
            try {
                const res = await fetch('/@/forms');
                if (res.ok) {
                    forms.value = await res.json();
                }
            } catch (e) {
                console.error("Failed to fetch forms", e);
            }
        };

        const openBuilder = (id) => {
            activeId.value = id;
            view.value = 'builder';
        };

        const openSubmissions = (id) => {
            activeId.value = id;
            view.value = 'submissions';
        };

        const openStats = (id) => {
            activeId.value = id;
            view.value = 'stats';
        };

        const closeSubView = () => {
            view.value = 'list';
            activeId.value = null;
            fetchForms();
        };

        const deleteForm = async (id) => {
            if (!(await store.showConfirm('Delete Form', 'Are you sure? This will delete all submissions too.'))) return;
            try {
                await fetch(`/@/forms/${id}`, { method: 'DELETE' });
                fetchForms();
                store.addNotification('Form deleted', 'success');
            } catch (e) {
                store.addNotification('Failed to delete form', 'error');
            }
        };

        const getPublicLink = (slug) => {
            return `${window.location.origin}/f/${slug}`;
        };

        const copyLink = (slug) => {
            navigator.clipboard.writeText(getPublicLink(slug));
            store.addNotification('Link copied to clipboard', 'info');
        };

        onMounted(() => {
            fetchForms();
        });

        return {
            view, activeId, forms, sortedForms,
            sortColumn, sortDirection, sortBy, columns,
            openBuilder, openSubmissions, openStats, closeSubView, deleteForm, getPublicLink, copyLink
        };
    }
};

