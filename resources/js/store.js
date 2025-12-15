import { reactive, computed } from 'vue';

// 1. Reactive State
const params = new URLSearchParams(window.location.search);
const initialView = params.get('view') || 'todos';

const state = reactive({
    user: null,
    theme: localStorage.getItem('theme') || 'dark', // 'dark' | 'light'
    layout: localStorage.getItem('layout') || 'top', // 'top' | 'side'
    currentView: initialView, // 'dashboard', 'users', 'cms', 'map', etc.
    loginMode: 'login' // 'login' | 'register'
});

// 2. Computed Getters
const getters = {
    isAdmin: computed(() => state.user && state.user.level >= 100)
};

// 3. Standalone Actions (No 'this' issues)

// Helper to persist settings to backend if logged in
const savePreference = async (key, value) => {
    if (!state.user) return;

    // Optimistically update local user object
    if (!state.user.settings) state.user.settings = {};
    state.user.settings[key] = value;

    try {
        await fetch('/@/user/settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ key, value })
        });
    } catch (e) {
        console.error(`Failed to save ${key}`, e);
    }
};

const setTheme = (newTheme) => {
    state.theme = newTheme;
    localStorage.setItem('theme', newTheme);
    if (newTheme === 'light') {
        document.body.classList.add('light-theme');
    } else {
        document.body.classList.remove('light-theme');
    }
    savePreference('theme', newTheme);
};

const setLayout = (newLayout) => {
    state.layout = newLayout;
    localStorage.setItem('layout', newLayout);
    if (newLayout === 'side') {
        document.body.classList.add('layout-side');
    } else {
        document.body.classList.remove('layout-side');
    }
    savePreference('layout', newLayout);
};

const setUser = (user) => {
    state.user = user;
    if (user) {
        // Restore user preferences if available
        if (user.settings?.theme) {
            setTheme(user.settings.theme);
        }
        if (user.settings?.layout) {
            setLayout(user.settings.layout);
        }
    }
};

const toggleTheme = () => {
    const newTheme = state.theme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
};

const setView = (view) => {
    state.currentView = view;
};

const setLoginMode = (mode) => {
    state.loginMode = mode;
};

const checkSession = async () => {
    try {
        const res = await fetch('/@/user');
        if (res.ok) {
            const data = await res.json();
            setUser(data.user);
            return true;
        }
    } catch (e) {
        console.error("Session check failed", e);
    }
    return false;
};

const logout = async () => {
    try {
        await fetch('/@/logout', { method: 'POST' });
        state.user = null;
        state.currentView = 'todos'; // Reset view
        // Optional: reset theme to system default or keep current
    } catch (e) {
        console.error("Logout failed", e);
    }
};

// Export Store Object
// All methods are bound correctly since they are closures
export const store = {
    state,
    getters,
    setUser,
    setTheme,
    toggleTheme,
    setLayout,
    setView,
    setLoginMode,
    checkSession,
    logout,
    savePreference
};
