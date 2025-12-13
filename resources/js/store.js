import { reactive, computed } from 'vue';

// 1. Reactive State
const state = reactive({
    user: null,
    theme: localStorage.getItem('theme') || 'dark', // 'dark' | 'light'
    layout: localStorage.getItem('layout') || 'top', // 'top' | 'side'
    currentView: 'todos', // 'dashboard', 'users', 'cms', 'map', etc.
    loginMode: 'login' // 'login' | 'register'
});

// 2. Computed Getters
const getters = {
    isAdmin: computed(() => state.user && state.user.level >= 100)
};

// 3. Actions (State Mutations & Business Logic)
const actions = {
    setUser(user) {
        state.user = user;
        if (user) {
            // Restore user preferences if available
            if (user.settings?.theme) {
                this.setTheme(user.settings.theme);
            }
            if (user.settings?.layout) {
                this.setLayout(user.settings.layout);
            }
        }
    },

    setTheme(newTheme) {
        state.theme = newTheme;
        localStorage.setItem('theme', newTheme);
        if (newTheme === 'light') {
            document.body.classList.add('light-theme');
        } else {
            document.body.classList.remove('light-theme');
        }
        this.savePreference('theme', newTheme);
    },

    toggleTheme() {
        const newTheme = state.theme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    },

    setLayout(newLayout) {
        state.layout = newLayout;
        localStorage.setItem('layout', newLayout);
        if (newLayout === 'side') {
            document.body.classList.add('layout-side');
        } else {
            document.body.classList.remove('layout-side');
        }
        this.savePreference('layout', newLayout);
    },

    setView(view) {
        state.currentView = view;
    },

    setLoginMode(mode) {
        state.loginMode = mode;
    },

    async checkSession() {
        try {
            const res = await fetch('/api/user');
            if (res.ok) {
                const data = await res.json();
                this.setUser(data.user);
                return true;
            }
        } catch (e) {
            console.error("Session check failed", e);
        }
        return false;
    },

    async logout() {
        try {
            await fetch('/api/logout', { method: 'POST' });
            state.user = null;
            state.currentView = 'todos'; // Reset view
            // Optional: reset theme to system default or keep current
        } catch (e) {
            console.error("Logout failed", e);
        }
    },

    // Helper to persist settings to backend if logged in
    async savePreference(key, value) {
        if (!state.user) return;

        // Optimistically update local user object
        if (!state.user.settings) state.user.settings = {};
        state.user.settings[key] = value;

        try {
            await fetch('/api/user/settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ key, value })
            });
        } catch (e) {
            console.error(`Failed to save ${key}`, e);
        }
    }
};

// Export Store Object
export const store = {
    state,
    getters,
    ...actions
};
