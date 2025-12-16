import { ref, computed, watch } from 'vue';
import { store } from 'store';
import Icon from 'ui/Icon.js';

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
                await fetch('/@/user/settings', {
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

        // Debounce utility
        const debounce = (fn, delay) => {
            let timeoutId;
            return (...args) => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => fn(...args), delay);
            };
        };

        const saveDuration = async (val) => {
            localStorage.setItem('defaultDuration', val);
            saving.value = true;
            try {
                await fetch('/@/user/settings', {
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

        const debouncedSaveDuration = debounce(saveDuration, 500);

        watch(defaultDuration, (newVal) => {
            debouncedSaveDuration(newVal);
        });

        return {
            user,
            theme,
            setTheme,
            layout,
            setLayout,
            defaultDuration,
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
            
            <div class="admin-grid">
                <!-- Profile Card -->
                <div class="admin-card" v-if="user">
                    <h3>Profile</h3>
                    <div class="form-group">
                        <label>Username</label>
                        <div class="static-value">{{ user.username }}</div>
                    </div>
                    <div class="form-group">
                        <label>Access Level</label>
                        <div class="static-value">
                            <span class="badge-level" :class="{ 'admin': user.level >= 100 }">
                                Level {{ user.level }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Preferences Card -->
                <div class="admin-card">
                    <h3>Preferences</h3>
                    
                    <div class="form-group">
                        <label>Theme</label>
                        <div class="button-group">
                            <button 
                                @click="setTheme('dark')" 
                                :class="{ active: theme === 'dark' }"
                                :style="theme === 'dark' ? { border: '2px solid var(--accent-color)', background: 'rgba(var(--accent-color-rgb), 0.1)' } : {}"
                                :disabled="saving"
                                class="theme-btn"
                            >
                                <LucideIcon name="moon" size="18" class="btn-icon-left" /> Dark Mode
                                <LucideIcon v-if="theme === 'dark'" name="check" size="16" style="margin-left:auto;" />
                            </button>
                            <button 
                                @click="setTheme('light')" 
                                :class="{ active: theme === 'light' }"
                                :style="theme === 'light' ? { border: '2px solid var(--accent-color)', background: 'rgba(var(--accent-color-rgb), 0.1)' } : {}"
                                :disabled="saving"
                                class="theme-btn"
                            >
                                <LucideIcon name="sun" size="18" class="btn-icon-left" /> Light Mode
                                <LucideIcon v-if="theme === 'light'" name="check" size="16" style="margin-left:auto;" />
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Layout</label>
                        <div class="button-group">
                            <button 
                                @click="setLayout('side')" 
                                :class="{ active: layout === 'side' }"
                                :style="layout === 'side' ? { border: '2px solid var(--accent-color)', background: 'rgba(var(--accent-color-rgb), 0.1)' } : {}"
                                :disabled="saving"
                                class="theme-btn"
                            >
                                <LucideIcon name="arrow-left" size="18" class="btn-icon-left" /> Sidebar
                                <LucideIcon v-if="layout === 'side'" name="check" size="16" style="margin-left:auto;" />
                            </button>
                            <button 
                                @click="setLayout('top')" 
                                :style="layout === 'top' ? { border: '2px solid var(--accent-color)', background: 'rgba(var(--accent-color-rgb), 0.1)' } : {}"
                                :class="{ active: layout === 'top' }"
                                :disabled="saving"
                                class="theme-btn"
                            >
                                <LucideIcon name="arrow-up" size="18" class="btn-icon-left" /> Top Bar
                                <LucideIcon v-if="layout === 'top'" name="check" size="16" style="margin-left:auto;" />
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Default Todo Duration (days)</label>
                        <input 
                            type="number" 
                            v-model="defaultDuration"
                            min="1"
                            class="input-short"
                        >
                    </div>

                    <div class="form-group">
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
                        <div class="actions-group">
                            <input type="color" v-model="newColor" class="color-input">
                            <input v-model="newColor" placeholder="#RRGGBB" class="input-short">
                            <button @click="addColor" class="btn-small">Add Color</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
};

export default UserSettings;
