import { ref, reactive, onMounted, computed } from 'vue';
import Icon from './Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">
                    <LucideIcon name="globe" size="32" style="display:inline-block; vertical-align:middle; margin-right:12px;"></LucideIcon>
                    Multi-Site Management
                </h2>
                <div class="primary-actions">
                     <button v-if="!showForm" @click="showForm = true" class="btn-primary">
                        <LucideIcon name="plus" size="18" style="vertical-align:middle; margin-right:4px;"></LucideIcon> New Site
                     </button>
                </div>
            </div>
            
            <div class="admin-grid">
                <div class="admin-card">
                    <div v-if="showForm" class="cms-form" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                        <h3>Create New Site</h3>
                        <p style="color:var(--text-secondary); margin-bottom:15px;">
                            This will provision a new independent database. You must configure your web server or DNS to point <code>domain.com</code> to this installation.
                        </p>
                        <form @submit.prevent="createSite">
                            <div class="form-group">
                                <label>Domain Name</label>
                                <input v-model="form.domain" placeholder="example.com" required pattern="^[a-zA-Z0-9.\\-]+$" title="Alphanumeric, dots, hyphens only">
                                <small>The specific domain to serve this site (e.g. <code>client-a.com</code> or <code>sub.mysite.com</code>).</small>
                            </div>
                            <div class="form-actions">
                                <button type="submit" :disabled="creating">
                                    <LucideIcon v-if="creating" name="loader" class="spin" size="18"></LucideIcon>
                                    <span v-else>Create Site</span>
                                </button>
                                <button type="button" @click="showForm = false" class="btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <div v-if="loading" class="loading">Loading sites...</div>
                    <table v-else-if="sites.length" class="site-table">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Database Size</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="site in sites" :key="site.domain">
                                <td>
                                    <strong>{{ site.domain }}</strong>
                                    <div v-if="isCurrent(site.domain)" class="badge-level admin" style="display:inline-block; margin-left:8px; font-size:0.75em;">Current</div>
                                </td>
                                <td>{{ formatBytes(site.size) }}</td>
                                <td>{{ formatDate(site.created_at) }}</td>
                                <td class="actions">
                                    <a :href="'//' + site.domain" target="_blank" class="btn-small btn-secondary release-btn" style="text-decoration:none;">Visit</a>
                                    <button @click="deleteSite(site.domain)" class="btn-small btn-danger">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="empty-state">No additional sites configured. The default site is active.</p>
                </div>
            </div>
        </div>
    `,
    setup() {
        const sites = ref([]);
        const loading = ref(true);
        const showForm = ref(false);
        const creating = ref(false);
        const currentDomain = window.location.hostname;

        const form = reactive({
            domain: ''
        });

        const fetchSites = async () => {
            loading.value = true;
            try {
                const res = await fetch('/@/admin/sites');
                if (res.ok) {
                    sites.value = await res.json();
                }
            } catch (e) {
                console.error(e);
            } finally {
                loading.value = false;
            }
        };

        const createSite = async () => {
            creating.value = true;
            try {
                const res = await fetch('/@/admin/sites', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(form)
                });

                if (res.ok) {
                    await fetchSites();
                    showForm.value = false;
                    form.domain = '';
                    // Success feedback handled by list update
                } else {
                    const err = await res.json();
                    console.error('Failed to create site:', err.error);
                    // Use a toast or inline error ideally, for now console
                    alert('Error: ' + (err.error || 'Failed to create site'));
                }
            } catch (e) {
                console.error(e);
            } finally {
                creating.value = false;
            }
        };

        const deleteSite = async (domain) => {
            if (!confirm(`Are you sure you want to delete ${domain}? This will permanently destroy its database.`)) {
                return;
            }
            try {
                const res = await fetch(`/@/admin/sites/${domain}`, { method: 'DELETE' });
                if (res.ok) {
                    fetchSites();
                } else {
                    alert('Failed to delete site');
                }
            } catch (e) {
                console.error(e);
            }
        };

        const formatBytes = (bytes) => {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        };

        const formatDate = (timestamp) => {
            if (!timestamp) return '';
            return new Date(timestamp * 1000).toLocaleDateString();
        };

        const isCurrent = (domain) => {
            return domain === currentDomain;
        };

        onMounted(fetchSites);

        return {
            sites, loading, showForm, creating, form,
            createSite, deleteSite, formatBytes, formatDate, isCurrent
        };
    }
};
