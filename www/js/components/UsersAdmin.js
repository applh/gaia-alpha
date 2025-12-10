import { ref, reactive, onMounted } from 'vue';
import SortTh from './SortTh.js';
import Modal from './Modal.js';
import { useSorting } from '../composables/useSorting.js';
import { useCrud } from '../composables/useCrud.js';

export default {
    components: { SortTh, Modal },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">User Management</h2>
                <button @click="openCreateModal">Add User</button>
            </div>
            
            <div class="admin-card">
            <div v-if="loading" class="loading">Loading...</div>
            <table v-else-if="users.length">
                <thead>
                    <tr>
                        <SortTh name="id" label="ID" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                        <SortTh name="username" label="Username" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                        <SortTh name="level" label="Level" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                        <SortTh name="created_at" label="Created" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                        <SortTh name="updated_at" label="Updated" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                        <SortTh name="role" label="Role" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy" />
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in sortedUsers" :key="user.id">
                        <td>{{ user.id }}</td>
                        <td>{{ user.username }}</td>
                        <td>{{ user.level }}</td>
                        <td>{{ new Date(user.created_at).toLocaleString() }}</td>
                        <td>{{ new Date(user.updated_at).toLocaleString() }}</td>
                        <td>{{ user.level >= 100 ? 'Admin' : 'Member' }}</td>
                        <td>
                            <button @click="openEditModal(user)">Edit</button>
                            <button @click="deleteItem(user.id)" class="danger">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else>No users found.</p>

            <Modal :show="showModal" :title="editMode ? 'Edit User' : 'Create User'" @close="showModal = false">
                <form @submit.prevent="saveUser">
                    <div class="form-group" v-if="!editMode">
                        <label>Username:</label>
                        <input v-model="form.username" required>
                    </div>
                    <div class="form-group">
                        <label>Password {{ editMode ? '(leave blank to keep)' : '' }}:</label>
                        <input v-model="form.password" type="password" :required="!editMode">
                    </div>
                    <div class="form-group">
                        <label>Level:</label>
                        <input v-model.number="form.level" type="number" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit">{{ editMode ? 'Update' : 'Create' }}</button>
                        <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
                    </div>
                    <p v-if="error" class="error">{{ error }}</p>
                </form>
            </Modal>
        </div>
    </div>
    `,
    setup() {
        // Integrate useCrud
        const { items: users, loading, error, fetchItems, createItem, updateItem, deleteItem } = useCrud('/api/admin/users');

        // Sorting
        const { sortColumn, sortDirection, sortBy, sortedData: sortedUsers } = useSorting(users, 'id', 'asc', {
            role: (u) => u.level >= 100 ? 'Admin' : 'Member'
        });

        const showModal = ref(false);
        const editMode = ref(false);
        const form = reactive({
            id: null,
            username: '',
            password: '',
            level: 10
        });

        const openCreateModal = () => {
            editMode.value = false;
            form.username = '';
            form.password = '';
            form.level = 10;
            form.id = null;
            error.value = '';
            showModal.value = true;
        };

        const openEditModal = (user) => {
            editMode.value = true;
            form.id = user.id;
            form.username = user.username;
            form.password = '';
            form.level = user.level;
            error.value = '';
            showModal.value = true;
        };

        const saveUser = async () => {
            try {
                if (editMode.value) {
                    await updateItem(form.id, form);
                } else {
                    await createItem(form);
                }
                showModal.value = false;
            } catch (e) {
                // Error is already set by useCrud, but we catch here to prevent modal closing if needed
                // actually useCrud sets error.value but throws.
                console.error("Save failed", e);
            }
        };

        onMounted(fetchItems);

        return {
            users, loading, error,
            showModal, editMode, form,
            openCreateModal, openEditModal, saveUser, deleteItem,
            sortBy, sortColumn, sortDirection, sortedUsers
        };
    }
};
