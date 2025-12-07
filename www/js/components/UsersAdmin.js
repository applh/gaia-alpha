
import { ref, onMounted } from 'vue';

export default {
    template: `
        <div class="users-admin">
            <h2>User Management</h2>
            <table v-if="users.length">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Level</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id">
                        <td>{{ user.id }}</td>
                        <td>{{ user.username }}</td>
                        <td>{{ user.level }}</td>
                        <td>{{ user.level >= 100 ? 'Admin' : 'Member' }}</td>
                    </tr>
                </tbody>
            </table>
            <p v-else>No users found.</p>
        </div>
    `,
    setup() {
        const users = ref([]);

        const fetchUsers = async () => {
            const res = await fetch('/api/admin/users');
            if (res.ok) {
                users.value = await res.json();
            }
        };

        onMounted(fetchUsers);

        return { users };
    }
};
