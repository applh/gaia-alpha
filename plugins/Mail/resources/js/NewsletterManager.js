import { ref, onMounted } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
    <div class="newsletter-manager">
        <div class="admin-header">
            <h2 class="page-title">
                <LucideIcon name="send" size="32" />
                Newsletters
            </h2>
            <button @click="createNew" class="btn btn-primary">
                <LucideIcon name="plus" size="16" /> Create New
            </button>
        </div>

        <div class="admin-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Sent At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="newsletters.length === 0">
                        <td colspan="5" style="text-align: center; color: #666;">No newsletters found. Create one to get started.</td>
                    </tr>
                    <tr v-for="item in newsletters" :key="item.id">
                        <td>{{ item.subject }}</td>
                        <td>
                            <span :class="'badge badge-' + item.status">{{ item.status }}</span>
                        </td>
                        <td>{{ formatDate(item.created_at) }}</td>
                        <td>{{ item.sent_at ? formatDate(item.sent_at) : '-' }}</td>
                        <td>
                            <button @click="edit(item)" class="btn btn-sm">
                                <LucideIcon name="edit" size="14" />
                            </button>
                            <button @click="deleteItem(item.id)" class="btn btn-sm btn-danger">
                                <LucideIcon name="trash" size="14" />
                            </button>
                             <button v-if="item.status === 'draft'" @click="send(item)" class="btn btn-sm btn-success">
                                <LucideIcon name="send" size="14" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    `,
    setup() {
        const newsletters = ref([]);

        const loadNewsletters = async () => {
            const res = await fetch('/@/mail/newsletters');
            if (res.ok) {
                newsletters.value = await res.json();
            }
        };

        const createNew = () => {
            // Navigate to builder
            window.location.hash = '#/mail/newsletter-editor?id=new';
        };

        const edit = (item) => {
            window.location.hash = '#/mail/newsletter-editor?id=' + item.id;
        };

        const deleteItem = async (id) => {
            if (!confirm("Are you sure?")) return;
            await fetch(`/@/mail/newsletters/${id}`, { method: 'DELETE' });
            loadNewsletters();
        };

        const send = async (item) => {
            if (!confirm("Ready to send this newsletter?")) return;
            // Basic implementation calls the send endpoint
            const res = await fetch(`/@/mail/newsletters/${item.id}/send`, { method: 'POST' });
            const data = await res.json();
            if (data.status === 'success') {
                alert('Newsletter sent!');
                loadNewsletters();
            } else {
                alert('Error: ' + data.error);
            }
        };

        const formatDate = (d) => new Date(d).toLocaleDateString() + ' ' + new Date(d).toLocaleTimeString();

        onMounted(() => {
            loadNewsletters();
        });

        return { newsletters, createNew, edit, deleteItem, send, formatDate };
    }
}
