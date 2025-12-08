import { createApp, ref, defineAsyncComponent, onMounted, computed } from 'vue';

const Login = defineAsyncComponent(() => import('./components/Login.js'));
const TodoList = defineAsyncComponent(() => import('./components/TodoList.js'));
const AdminDashboard = defineAsyncComponent(() => import('./components/AdminDashboard.js'));
const UsersAdmin = defineAsyncComponent(() => import('./components/UsersAdmin.js'));
const CMS = defineAsyncComponent(() => import('./components/CMS.js'));
const DatabaseManager = defineAsyncComponent(() => import('./components/DatabaseManager.js'));
const UserSettings = defineAsyncComponent(() => import('./components/UserSettings.js'));

const App = {
    components: { Login },
    template: `
        <div class="app-container">
            <header>
                <a href="/" style="text-decoration: none; color: inherit;">
                    <h1>Gaia Alpha</h1>
                </a>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <nav v-if="user" style="display: flex; align-items: center; gap: 10px;">
                        <!-- Admin Menu -->
                        <template v-if="isAdmin">
                            <button @click="setView('dashboard')" :class="{ active: currentView === 'dashboard' }">📊 Dashboard</button>
                            <button @click="setView('todos')" :class="{ active: currentView === 'todos' }">✅ My Todos</button>
                            <button @click="setView('users')" :class="{ active: currentView === 'users' }">👥 Users</button>
                            <button @click="setView('cms')" :class="{ active: currentView === 'cms' }">📝 CMS</button>
                            <button @click="setView('database')" :class="{ active: currentView === 'database' }">🗄️ Database</button>
                        </template>

                        <!-- Member Menu -->
                        <template v-else>
                            <button @click="setView('todos')" :class="{ active: currentView === 'todos' }">✅ My Todos</button>
                            <button @click="setView('cms')" :class="{ active: currentView === 'cms' }">📝 CMS</button>
                        </template>
                        
                        <button @click="setView('settings')" :class="{ active: currentView === 'settings' }" style="opacity: 0.8;">⚙️ Settings</button>
                        
                        <button @click="toggleTheme" class="theme-toggle" :title="'Toggle Theme (' + theme + ')'">
                            {{ theme === 'dark' ? '☀️' : '🌙' }}
                        </button>
                        
                        <button @click="logout" class="logout-btn" :title="'Logout (' + user.username + ')'">🚪</button>
                    </nav>
                    <nav v-else style="display: flex; align-items: center; gap: 10px;">
                        <button @click="toggleTheme" class="theme-toggle" :title="'Toggle Theme (' + theme + ')'">
                            {{ theme === 'dark' ? '☀️' : '🌙' }}
                        </button>
                        <button @click="setLoginMode('login')" :class="{ active: loginMode === 'login' }">🔑 Login</button>
                        <button @click="setLoginMode('register')" :class="{ active: loginMode === 'register' }">✨ Register</button>
                    </nav>
                </div>
            </header>
            
            <main>
                <div v-if="!user">
                    <Login :mode="loginMode" @login="onLogin" />
                </div>
                <div v-else>
                    <keep-alive>
                        <component :is="currentComponent" />
                    </keep-alive>
                </div>
            </main>
        </div>
    `,
    setup() {
        const user = ref(null);
        const currentView = ref('todos');
        const loginMode = ref('login');
        const theme = ref(localStorage.getItem('theme') || 'dark');
        const layout = ref(localStorage.getItem('layout') || 'top'); // 'top' or 'side'

        const isAdmin = computed(() => user.value && user.value.level >= 100);

        const currentComponent = computed(() => {
            if (!user.value) return Login;
            switch (currentView.value) {
                case 'dashboard': return isAdmin.value ? AdminDashboard : TodoList;
                case 'users': return isAdmin.value ? UsersAdmin : TodoList;
                case 'cms': return CMS;
                case 'database': return isAdmin.value ? DatabaseManager : TodoList;
                case 'settings': return UserSettings;
                case 'todos': default: return TodoList;
            }
        });

        const toggleTheme = async () => {
            theme.value = theme.value === 'dark' ? 'light' : 'dark';
            applyTheme();
            localStorage.setItem('theme', theme.value);

            // Persist to API
            if (user.value) {
                try {
                    await fetch('/api/settings', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ key: 'theme', value: theme.value })
                    });
                } catch (e) {
                    console.error("Failed to save theme preference");
                }
            }
        };

        const applyTheme = () => {
            if (theme.value === 'light') {
                document.body.classList.add('light-theme');
            } else {
                document.body.classList.remove('light-theme');
            }
        };

        const setLayout = async (mode) => {
            if (layout.value === mode) return;
            layout.value = mode;
            applyLayout();
            localStorage.setItem('layout', mode);
            // Persist to API
            if (user.value) {
                try {
                    await fetch('/api/settings', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ key: 'layout', value: mode })
                    });
                } catch (e) {
                    console.error("Failed to save layout preference");
                }
            }
        };

        const applyLayout = () => {
            if (layout.value === 'side') {
                document.body.classList.add('layout-side');
            } else {
                document.body.classList.remove('layout-side');
            }
        };

        const fetchSettings = async () => {
            if (!user.value) return;
            try {
                const res = await fetch('/api/settings');
                if (res.ok) {
                    const data = await res.json();
                    if (data.settings) {
                        if (data.settings.theme) {
                            theme.value = data.settings.theme;
                            applyTheme();
                            localStorage.setItem('theme', theme.value);
                        }
                        if (data.settings.layout) {
                            layout.value = data.settings.layout;
                            applyLayout();
                            localStorage.setItem('layout', layout.value);
                        }
                    }
                }
            } catch (e) { console.error("Failed to load settings"); }
        };

        const checkAuth = async () => {
            try {
                const res = await fetch('/api/user');
                const data = await res.json();
                user.value = data.user;
                if (user.value) {
                    await fetchSettings();
                    setDefaultView();
                }
            } catch (e) {
                console.error("Auth check failed", e);
            }
        };

        const onLogin = async (loggedInUser) => {
            user.value = loggedInUser;
            await fetchSettings();
            setDefaultView();
        };

        const logout = async () => {
            await fetch('/api/logout', { method: 'POST' });
            user.value = null;
            currentView.value = 'todos';
            loginMode.value = 'login';
        };

        const setView = (view) => {
            currentView.value = view;
        };

        const setLoginMode = (mode) => {
            loginMode.value = mode;
        };

        const setDefaultView = () => {
            if (isAdmin.value) {
                currentView.value = 'dashboard';
            } else {
                currentView.value = 'todos';
            }
        };

        onMounted(() => {
            checkAuth();
            applyTheme();
            applyLayout();
        });

        return { user, onLogin, logout, currentView, setView, isAdmin, currentComponent, loginMode, setLoginMode, theme, toggleTheme, layout, setLayout };
    }
};

createApp(App).mount('#app');
