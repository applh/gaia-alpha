import { ref, computed } from 'vue';
import { store } from '../store.js';
import Icon from './Icon.js';

const UserSettings = {
    name: 'UserSettings',
    components: { LucideIcon: Icon },

    setup() {
        // Todo Palette Logic (remain local request logic for now, or move to store if generic)
        const defaultPalette = ['#FF6B6B', '#4ECDC4', '#FFE66D', '#1A535C', '#F7FFF7'];
        const palette = ref(JSON.parse(localStorage.getItem('todo_palette')) || defaultPalette);
        const newColor = ref('#000000');
        const saving = ref(false);

        const savePalette = async () => {
            localStorage.setItem('todo_palette', JSON.stringify(palette.value));
            saving.value = true;
            try {
                await fetch('/api/user/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ key: 'todo_palette', value: JSON.stringify(palette.value) })
                });
            } catch (e) {
                console.error("Failed to save palette", e);
            } finally {
                saving.value = false;
            }
        };

        const addColor = () => {
            if (/^#[0-9A-F]{6}$/i.test(newColor.value) && !palette.value.includes(newColor.value)) {
                palette.value.push(newColor.value);
                savePalette();
            }
        };

        const removeColor = (color) => {
            palette.value = palette.value.filter(c => c !== color);
            savePalette();
        };

        // Theme & Layout delegated to Store
        const user = computed(() => store.state.user);
        const theme = computed(() => store.state.theme);
        const setTheme = (newTheme) => {
            store.setTheme(newTheme);
        };

        const layout = computed(() => store.state.layout);
        const setLayout = (newLayout) => {
            store.setLayout(newLayout);
        };

        const defaultDuration = ref(localStorage.getItem('defaultDuration') || '1');
        const setDuration = async (val) => {
            if (defaultDuration.value === val) return;
            defaultDuration.value = val;
            localStorage.setItem('defaultDuration', val);

            saving.value = true;
            try {
                await fetch('/api/user/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ key: 'default_todo_duration', value: val })
                });
            } catch (e) {
                console.error("Failed to save default duration", e);
            } finally {
                saving.value = false;
            }
        };

        return {
            user,
            theme,
            setTheme,
            layout,
            setLayout,
            defaultDuration,
            setDuration,
            palette,
            newColor,
            addColor,
            removeColor,
            saving
        };
    },
    template: `
        <div class="admin-page">
            <div class="admin-header">
                <h2 class="page-title">User Settings</h2>
            </div>
            
            <div class="admin-card" v-if="user">
                <h3>Profile</h3>
                <div class="form-group">
                    <label>Username</label>
                    <div style="font-size: 1.1rem; padding: 5px 0;">{{ user.username }}</div>
                </div>
                <div class="form-group">
                    <label>Access Level</label>
                    <div style="font-size: 1.1rem; padding: 5px 0;">
                        <span style="background: var(--accent-color); color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.9rem;">
                            Level {{ user.level }}
                        </span>
                    </div>
                </div>
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
                            <LucideIcon name="moon" size="18" style="display:inline-block; vertical-align:middle; margin-right:8px;"></LucideIcon> Dark Mode
                        </button>
                        <button 
                            @click="setTheme('light')" 
                            :class="{ active: theme === 'light' }"
                            :disabled="saving"
                            class="theme-btn"
                        >
                            <LucideIcon name="sun" size="18" style="display:inline-block; vertical-align:middle; margin-right:8px;"></LucideIcon> Light Mode
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
                            <LucideIcon name="arrow-up" size="18" style="display:inline-block; vertical-align:middle; margin-right:8px;"></LucideIcon> Top Bar
                        </button>
                        <button 
                            @click="setLayout('side')" 
                            :class="{ active: layout === 'side' }"
                            :disabled="saving"
                            class="theme-btn"
                        >
                            <LucideIcon name="arrow-left" size="18" style="display:inline-block; vertical-align:middle; margin-right:8px;"></LucideIcon> Sidebar
                        </button>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Default Todo Duration (days)</label>
                    <input 
                        type="number" 
                        :value="defaultDuration" 
                        @change="setDuration($event.target.value)" 
                        min="1"
                        style="width: 100px;"
                    >
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Todo Color Palette</label>
                    <div class="palette-manager">
                        <div 
                            v-for="color in palette" 
                            :key="color" 
                            class="palette-swatch-manage"
                            :style="{ backgroundColor: color }"
                        >
                            <button @click="removeColor(color)" class="remove-swatch" title="Remove">×</button>
                        </div>
                    </div>
                    <div style="margin-top: 10px; display: flex; gap: 5px;">
                        <input type="color" v-model="newColor">
                        <input v-model="newColor" placeholder="#RRGGBB" style="width: 80px;">
                        <button @click="addColor" class="btn-small">Add Color</button>
                    </div>
                </div>
            </div>
        </div>
    `
};

export default UserSettings;
