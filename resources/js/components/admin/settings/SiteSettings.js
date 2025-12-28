import { ref, reactive, onMounted } from 'vue';
import Icon from 'ui/Icon.js';
import ImageSelector from 'ui/ImageSelector.js';
import Form from 'ui/Form.js';

export default {
    components: { LucideIcon: Icon, ImageSelector, Form },
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

                    <Form :action="saveSettings">
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
                    </Form>
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
            // Save each key individually as the API expects single key/value
            const promises = Object.keys(settings).map(key => {
                return fetch('/@/admin/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ key, value: settings[key] })
                });
            });

            // If any fail, Form will catch the error
            const responses = await Promise.all(promises);
            // Optional: check individual responses if needed
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
        };

        onMounted(fetchSettings);

        return { settings, saveSettings, showSelector, openSelector, handleImageSelect, showRobotsTips };
    }
};
