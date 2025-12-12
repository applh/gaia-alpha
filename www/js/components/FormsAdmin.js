
import { ref, onMounted } from 'vue';
import FormBuilder from './FormBuilder.js';
import FormSubmissions from './FormSubmissions.js';
import SortTh from './SortTh.js';
import { useSorting } from '../composables/useSorting.js';

export default {
    components: { FormBuilder, FormSubmissions, SortTh },
    template: `
        <div>
            <!-- List View -->
            <div v-if="view === 'list'" class="admin-page">
                <div class="admin-header">
                    <h2 class="page-title">My Forms</h2>
                    <button class="btn-primary" @click="openBuilder(null)">+ Create New Form</button>
                </div>
                
                <div class="admin-card">
                    <table v-if="forms.length">
                        <thead>
                            <tr>
                                <SortTh label="ID" name="id" :currentSort="sortColumn" :sortDir="sortDirection" @sort="sortBy" />
                                <SortTh label="Title" name="title" :currentSort="sortColumn" :sortDir="sortDirection" @sort="sortBy" />
                                <SortTh label="Public Link" name="slug" :currentSort="sortColumn" :sortDir="sortDirection" @sort="sortBy" />
                                <SortTh label="Created" name="created_at" :currentSort="sortColumn" :sortDir="sortDirection" @sort="sortBy" />
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="form in sortedForms" :key="form.id">
                                <td>#{{ form.id }}</td>
                                <td><strong>{{ form.title }}</strong></td>
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a :href="getPublicLink(form.slug)" target="_blank" class="text-link">/f/{{ form.slug }}</a>
                                        <button class="btn-small btn-icon" @click="copyLink(form.slug)" title="Copy Link">
                                            <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>{{ new Date(form.created_at).toLocaleDateString() }}</td>
                                <td>
                                    <div class="actions-group">
                                        <button class="btn-small" @click="openBuilder(form.id)">Edit</button>
                                        <button class="btn-small" @click="openSubmissions(form.id)">Submissions</button>
                                        <button class="btn-small danger" @click="deleteForm(form.id)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="empty-state">
                        <p>No forms created yet.</p>
                        <button class="btn-primary" @click="openBuilder(null)">Create your first form</button>
                    </div>
                </div>
            </div>

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
        </div>
    `,
    setup() {
        const view = ref('list');
        const activeId = ref(null);
        const forms = ref([]);

        const { sortColumn, sortDirection, sortBy, sortedData: sortedForms } = useSorting(forms, 'created_at', 'desc');

        const fetchForms = async () => {
            try {
                const res = await fetch('/api/forms');
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

        const closeSubView = () => {
            view.value = 'list';
            activeId.value = null;
            fetchForms();
        };

        const deleteForm = async (id) => {
            if (!confirm('Are you sure? This will delete all submissions too.')) return;
            try {
                await fetch(`/api/forms/${id}`, { method: 'DELETE' });
                fetchForms();
            } catch (e) {
                console.error("Failed to delete form", e);
            }
        };

        const getPublicLink = (slug) => {
            return `${window.location.origin}/f/${slug}`;
        };

        const copyLink = (slug) => {
            navigator.clipboard.writeText(getPublicLink(slug));
            // Feedback could be added here
        };

        onMounted(() => {
            fetchForms();
        });

        return {
            view, activeId, forms, sortedForms,
            sortColumn, sortDirection, sortBy,
            openBuilder, openSubmissions, closeSubView, deleteForm, getPublicLink, copyLink
        };
    }
};
