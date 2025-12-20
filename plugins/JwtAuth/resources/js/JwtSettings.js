import { ref, onMounted } from 'vue';
import Icon from 'ui/Icon.js';
import { store } from 'store';

export default {
    components: { LucideIcon: Icon },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">
                    <LucideIcon name="key" size="32" style="display:inline-block; vertical-align:middle; margin-right:12px;"></LucideIcon>
                    JWT Auth Settings
                </h2>
                <div class="actions">
                    <button v-if="isDirty" class="btn btn-success" @click="saveChanges" :disabled="saving" style="margin-right: 10px;">
                        <LucideIcon name="save" size="18" style="display:inline-block; vertical-align:middle; margin-right:6px;"></LucideIcon>
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <button class="btn btn-danger" @click="confirmRefresh" :disabled="refreshing">
                        <LucideIcon name="refresh-cw" size="18" style="display:inline-block; vertical-align:middle; margin-right:6px;"></LucideIcon>
                        Refresh Secret
                    </button>
                </div>
            </div>

            <div v-if="loading" class="admin-card">Loading settings...</div>
            <div v-else-if="error" class="admin-card error">{{ error }}</div>
            
            <div v-else class="admin-card">
                <div class="form-group">
                    <label>Default Token TTL (Seconds)</label>
                    <input type="number" v-model="settings.ttl" class="form-control" @input="isDirty = true">
                    <small>Default lifetime for newly generated tokens. Current: {{ Math.round(settings.ttl / 60) }} minutes.</small>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Signing Secret</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" :value="settings.secret || '••••••••••••••••'" class="form-control" readonly style="background: var(--bg-main);">
                    </div>
                    <small class="warning-text" style="color: var(--error-color);">Warning: Refreshing the secret will invalidate all currently active tokens.</small>
                </div>
            </div>

            <div class="admin-card" style="margin-top: 20px;">
                <h3>Usage Info</h3>
                <p>To authenticate via JWT, include the following header in your requests:</p>
                <div class="code-block" style="background: var(--bg-main); padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0;">
                    Authorization: Bearer &lt;your_token&gt;
                </div>
                <p>You can generate tokens using the CLI:</p>
                <div class="code-block" style="background: var(--bg-main); padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0;">
                    php cli.php jwt:generate --user=admin
                </div>
            </div>
        </div>
    `,
    setup() {
        const settings = ref({ ttl: 3600, secret: '' });
        const loading = ref(true);
        const error = ref(null);
        const isDirty = ref(false);
        const saving = ref(false);
        const refreshing = ref(false);

        const fetchSettings = async () => {
            loading.value = true;
            try {
                const res = await fetch('/@/admin/jwt/settings');
                if (!res.ok) throw new Error('Failed to load JWT settings');
                settings.value = await res.json();
                isDirty.value = false;
            } catch (e) {
                error.value = e.message;
            } finally {
                loading.value = false;
            }
        };

        const saveChanges = async () => {
            saving.value = true;
            try {
                const res = await fetch('/@/admin/jwt/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(settings.value)
                });
                if (!res.ok) throw new Error('Failed to save settings');
                isDirty.value = false;
                store.addNotification('Settings saved successfully', 'success');
            } catch (e) {
                store.addNotification(e.message, 'error');
            } finally {
                saving.value = false;
            }
        };

        const confirmRefresh = () => {
            if (confirm('Are you sure you want to refresh the signing secret? This will immediately logout all API users.')) {
                refreshSecret();
            }
        };

        const refreshSecret = async () => {
            refreshing.value = true;
            try {
                const res = await fetch('/@/admin/jwt/refresh-secret', { method: 'POST' });
                if (!res.ok) throw new Error('Failed to refresh secret');
                const data = await res.json();
                settings.value.secret = data.secret;
                store.addNotification('Secret refreshed! Old tokens are now invalid.', 'success');
            } catch (e) {
                store.addNotification(e.message, 'error');
            } finally {
                refreshing.value = false;
            }
        };

        onMounted(fetchSettings);

        return {
            settings, loading, error, isDirty, saving, saveChanges,
            refreshing, confirmRefresh
        };
    }
};
