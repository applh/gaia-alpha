import { ref, reactive, onMounted } from 'vue';
import Icon from './Icon.js';
import ImageSelector from './ImageSelector.js';

export default {
    components: { LucideIcon: Icon, ImageSelector },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">
                    <LucideIcon name="globe" size="32" style="display:inline-block; vertical-align:middle; margin-right:12px;"></LucideIcon>
                    Site Settings
                </h2>
            </div>
            
            <div class="admin-grid">
                <div class="admin-card">
                    <h3>General Information</h3>
                    <p style="color:var(--text-secondary); margin-bottom:20px;">
                        These settings control how your site appears in search engines and browser tabs.
                    </p>

                    <form @submit.prevent="saveSettings">
                        <div class="form-group">
                            <label>Site Title</label>
                            <input v-model="settings.site_title" placeholder="e.g. Gaia Alpha">
                            <small>Default title for pages that don't specify one.</small>
                        </div>

                        <div class="form-group">
                            <label>Site Language</label>
                            <input v-model="settings.site_language" placeholder="en" style="width: 80px;">
                            <small>Two-letter language code (e.g. en, fr, es).</small>
                        </div>

                        <div class="form-group">
                            <label>Meta Description</label>
                            <textarea v-model="settings.site_description" rows="3" placeholder="Brief description of your site for SEO."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Meta Keywords</label>
                            <input v-model="settings.site_keywords" placeholder="keyword1, keyword2, keyword3">
                        </div>

                        <div class="form-group">
                            <label>Favicon</label>
                            <div class="image-input-group">
                                <input v-model="settings.site_favicon" placeholder="https://..." readonly @click="openSelector('favicon')">
                                <button type="button" @click="openSelector('favicon')" class="btn-secondary">Select</button>
                            </div>
                            <div v-if="settings.site_favicon" style="margin-top:10px;">
                                <img :src="settings.site_favicon" style="width:32px; height:32px; object-fit:contain; background:#eee; padding:4px; border-radius:4px;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Site Logo</label>
                            <div class="image-input-group">
                                <input v-model="settings.site_logo" placeholder="https://..." readonly @click="openSelector('logo')">
                                <button type="button" @click="openSelector('logo')" class="btn-secondary">Select</button>
                            </div>
                            <div v-if="settings.site_logo" style="margin-top:10px;">
                                <img :src="settings.site_logo" style="max-height: 50px; object-fit:contain; background:#eee; padding:4px; border-radius:4px;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Robots.txt Content</label>
                            <textarea v-model="settings.robots_txt" rows="5" placeholder="User-agent: *&#10;Allow: /" style="font-family:monospace;"></textarea>
                            <small>
                                Leave empty to use default. 
                                <a href="#" @click.prevent="showRobotsTips = !showRobotsTips" style="margin-left:10px;">{{ showRobotsTips ? 'Hide Tips' : 'Show Tips' }}</a>
                            </small>
                            <div v-if="showRobotsTips" style="background:var(--bg-secondary); padding:10px; border-radius:4px; margin-top:8px; font-size:0.85em; color:var(--text-secondary);">
                                <strong>Tips:</strong><br>
                                Public: <code>User-agent: *<br>Allow: /</code><br>
                                Private: <code>User-agent: *<br>Disallow: /</code>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary" :disabled="saving" style="min-width: 140px;">
                                <LucideIcon v-if="saveStatus === 'saving'" name="loader" class="spin" size="18"></LucideIcon>
                                <LucideIcon v-else-if="saveStatus === 'success'" name="check" size="18"></LucideIcon>
                                <LucideIcon v-else-if="saveStatus === 'error'" name="alert-circle" size="18"></LucideIcon>
                                <span v-else>Save Changes</span>
                                <span v-if="saveStatus === 'success'" style="margin-left:8px;">Saved!</span>
                                <span v-if="saveStatus === 'error'" style="margin-left:8px;">Error</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <ImageSelector 
                :show="showSelector" 
                @close="showSelector = false"
                @select="handleImageSelect"
            />
        </div>
    `,
    setup() {
        const settings = reactive({
            site_title: '',
            site_language: 'en',
            site_description: '',
            site_keywords: '',
            site_favicon: '',
            site_logo: '',
            robots_txt: ''
        });
        const saving = ref(false);
        const saveStatus = ref('idle'); // idle, saving, success, error
        const showSelector = ref(false);
        const selectorMode = ref('');
        const showRobotsTips = ref(false);

        const fetchSettings = async () => {
            try {
                const res = await fetch('/@/admin/settings');
                if (res.ok) {
                    const data = await res.json();
                    if (data.settings) {
                        Object.assign(settings, data.settings);
                    }
                }
            } catch (e) {
                console.error("Failed to load settings", e);
            }
        };

        const saveSettings = async () => {
            saving.value = true;
            saveStatus.value = 'saving';
            try {
                // Save each key individually as the API expects single key/value
                // Or update API to handle bulk?
                // API implementation in SettingsController::updateGlobal takes {key, value}.
                // So we loop.
                const promises = Object.keys(settings).map(key => {
                    return fetch('/@/admin/settings', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ key, value: settings[key] })
                    });
                });

                await Promise.all(promises);
                saveStatus.value = 'success';
                setTimeout(() => { saveStatus.value = 'idle'; }, 2000);
            } catch (e) {
                console.error("Failed to save settings", e);
                saveStatus.value = 'error';
                setTimeout(() => { saveStatus.value = 'idle'; }, 3000);
            } finally {
                saving.value = false;
            }
        };

        const openSelector = (mode) => {
            selectorMode.value = mode;
            showSelector.value = true;
        };

        const handleImageSelect = (img) => {
            if (selectorMode.value === 'favicon') {
                settings.site_favicon = img.image;
            } else if (selectorMode.value === 'logo') {
                settings.site_logo = img.image;
            }
            // Add other image fields here if needed (e.g. Logo)
        };

        onMounted(fetchSettings);

        return { settings, saving, saveStatus, saveSettings, showSelector, openSelector, handleImageSelect, showRobotsTips };
    }
};
