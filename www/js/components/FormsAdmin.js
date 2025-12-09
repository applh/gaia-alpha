
import { ref, onMounted } from 'vue';
import FormBuilder from './FormBuilder.js';
import FormSubmissions from './FormSubmissions.js';

export default {
    components: { FormBuilder, FormSubmissions },
    template: `
        <div>
            <!-- List View -->
            <div v-if="view === 'list'" class="admin-page">
                <div class="admin-header">
                    <h2 class="page-title">My Forms</h2>
                    <button @click="openBuilder(null)">+ Create New Form</button>
                </div>
                
                <div class="admin-card">
                    <table v-if="forms.length">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Public Link</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="form in forms" :key="form.id">
                                <td>{{ form.title }}</td>
                                <td>
                                    <a :href="getPublicLink(form.slug)" target="_blank">{{ form.slug }}</a>
                                    <button class="btn-small" @click="copyLink(form.slug)">📋</button>
                                </td>
                                <td>{{ new Date(form.created_at).toLocaleDateString() }}</td>
                                <td>
                                    <button @click="openBuilder(form.id)">Edit</button>
                                    <button @click="openSubmissions(form.id)">Submissions</button>
                                    <button @click="deleteForm(form.id)" class="danger">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else>No forms created yet.</p>
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

        const fetchForms = async () => {
            const res = await fetch('/api/forms');
            if (res.ok) {
                forms.value = await res.json();
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
            // if (!confirm('Are you sure? This will delete all submissions too.')) return;
            await fetch(`/api/forms/${id}`, { method: 'DELETE' });
            fetchForms();
        };

        const getPublicLink = (slug) => {
            return `${window.location.origin}/f/${slug}`;
        };

        const copyLink = (slug) => {
            navigator.clipboard.writeText(getPublicLink(slug));
            alert('Link copied!');
        };

        onMounted(fetchForms);

        return { view, activeId, forms, openBuilder, openSubmissions, closeSubView, deleteForm, getPublicLink, copyLink };
    }
};
