import { ref } from 'vue';

const UserSettings = {
    name: 'UserSettings',

    setup() {
        const theme = ref(localStorage.getItem('theme') || 'dark');
        const saving = ref(false);

        const setTheme = async (newTheme) => {
            if (theme.value === newTheme) return;
            theme.value = newTheme;

            document.body.classList.toggle('light-theme', newTheme === 'light');
            localStorage.setItem('theme', newTheme);

            // Persist to API
            saving.value = true;
            try {
                await fetch('/api/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ key: 'theme', value: newTheme })
                });
            } catch (e) {
                console.error("Failed to save theme", e);
            } finally {
                saving.value = false;
            }
        };

        const layout = ref(localStorage.getItem('layout') || 'top');
        const setLayout = async (mode) => {
            if (layout.value === mode) return;
            layout.value = mode;

            if (mode === 'side') {
                document.body.classList.add('layout-side');
            } else {
                document.body.classList.remove('layout-side');
            }
            localStorage.setItem('layout', mode);

            // Persist to API
            saving.value = true;
            try {
                await fetch('/api/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ key: 'layout', value: mode })
                });
            } catch (e) {
                console.error("Failed to save layout", e);
            } finally {
                saving.value = false;
            }
        };

        return {
            theme,
            setTheme,
            layout,
            setLayout,
            saving
        };
    },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">User Settings</h2>
            </div>
            
            <div class="admin-card">
                <h3>Preferences</h3>
                
                <div class="form-group">
                    <label>Theme</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button 
                            @click="setTheme('dark')" 
                            :class="{ active: theme === 'dark' }"
                            :disabled="saving"
                            class="theme-btn"
                        >
                            🌙 Dark Mode
                        </button>
                        <button 
                            @click="setTheme('light')" 
                            :class="{ active: theme === 'light' }"
                            :disabled="saving"
                            class="theme-btn"
                        >
                            ☀️ Light Mode
                        </button>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 20px;">
                    <label>Layout</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button 
                            @click="setLayout('top')" 
                            :class="{ active: layout === 'top' }"
                            :disabled="saving"
                            class="theme-btn"
                        >
                            ⬆️ Top Bar
                        </button>
                        <button 
                            @click="setLayout('side')" 
                            :class="{ active: layout === 'side' }"
                            :disabled="saving"
                            class="theme-btn"
                        >
                            ⬅️ Sidebar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `
};

export default UserSettings;
