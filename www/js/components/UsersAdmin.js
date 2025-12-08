
import { ref, reactive, onMounted, computed } from 'vue';
import SortTh from './SortTh.js';
import { useSorting } from '../composables/useSorting.js';

export default {
    components: { SortTh },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">User Management</h2>
                <button @click="openCreateModal">Add User</button>
            </div>
            
            <div class="admin-card">

            <table v-if="users.length">
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
                            <button @click="deleteUser(user.id)" class="danger">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else>No users found.</p>

            <!-- Modal -->
            <div v-if="showModal" class="modal-overlay">
                <div class="modal">
                    <h3>{{ editMode ? 'Edit User' : 'Create User' }}</h3>
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
                            <button type="button" @click="closeModal">Cancel</button>
                        </div>
                        <p v-if="error" class="error">{{ error }}</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    `,
    setup() {
        const users = ref([]);
        const showModal = ref(false);
        const { sortColumn, sortDirection, sortBy, sortedData: sortedUsers } = useSorting(users, 'id', 'asc', {
            role: (u) => u.level >= 100 ? 'Admin' : 'Member'
        });
        const editMode = ref(false);
        const error = ref('');
        const form = reactive({
            id: null,
            username: '',
            password: '',
            level: 10
        });

        const fetchUsers = async () => {
            const res = await fetch('/api/admin/users');
            if (res.ok) {
                users.value = await res.json();
            }
        };

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
            form.username = user.username; // Display only usually, but let's keep it simple
            form.password = '';
            form.level = user.level;
            error.value = '';
            showModal.value = true;
        };

        const closeModal = () => {
            showModal.value = false;
        };

        const saveUser = async () => {
            error.value = '';
            const url = editMode.value ? `/api/admin/users/${form.id}` : '/api/admin/users';
            const method = editMode.value ? 'PATCH' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(form)
                });
                const data = await res.json();

                if (res.ok) {
                    closeModal();
                    fetchUsers();
                } else {
                    error.value = data.error || 'Operation failed';
                }
            } catch (e) {
                error.value = 'Network error';
            }
        };

        const deleteUser = async (id) => {

            try {
                const res = await fetch(`/api/admin/users/${id}`, { method: 'DELETE' });
                if (res.ok) {
                    fetchUsers();
                } else {
                    const data = await res.json();
                    alert(data.error || 'Failed to delete');
                }
            } catch (e) {
                alert('Network error');
            }
        };

        onMounted(fetchUsers);

        return { users, showModal, editMode, form, error, openCreateModal, openEditModal, closeModal, saveUser, deleteUser, sortBy, sortColumn, sortDirection, sortedUsers };
    }
};
