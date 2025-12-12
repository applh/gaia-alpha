import { createApp, defineAsyncComponent, onMounted, computed } from 'vue';
import { store } from './store.js';

const Login = defineAsyncComponent(() => import('./components/Login.js'));
const TodoList = defineAsyncComponent(() => import('./components/TodoList.js'));
const AdminDashboard = defineAsyncComponent(() => import('./components/AdminDashboard.js'));
const UsersAdmin = defineAsyncComponent(() => import('./components/UsersAdmin.js'));
const CMS = defineAsyncComponent(() => import('./components/CMS.js'));
const DatabaseManager = defineAsyncComponent(() => import('./components/DatabaseManager.js'));
const UserSettings = defineAsyncComponent(() => import('./components/UserSettings.js'));
const FormsAdmin = defineAsyncComponent(() => import('./components/FormsAdmin.js'));
const ApiManager = defineAsyncComponent(() => import('./components/ApiManager.js'));
const MapPanel = defineAsyncComponent(() => import('./components/MapPanel.js'));

const App = {
    components: { Login, LucideIcon: defineAsyncComponent(() => import('./components/Icon.js')) },
    template: `
        <div class="app-container">
            <header>
                <a href="/" style="text-decoration: none; color: inherit;">
                    <h1>Gaia Alpha</h1>
                </a>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <nav v-if="store.state.user" style="display: flex; align-items: center; gap: 10px;">
                        <!-- Admin Menu -->
                        <template v-if="isAdmin">
                            <button @click="store.setView('dashboard')" :class="{ active: store.state.currentView === 'dashboard' }">
                                <span class="nav-icon"><LucideIcon name="layout-dashboard" size="20"></LucideIcon></span> 
                                <span class="nav-label">Dashboard</span>
                            </button>
                            <button @click="store.setView('todos')" :class="{ active: store.state.currentView === 'todos' }">
                                <span class="nav-icon"><LucideIcon name="check-square" size="20"></LucideIcon></span>
                                <span class="nav-label">My Todos</span>
                            </button>
                            <button @click="store.setView('users')" :class="{ active: store.state.currentView === 'users' }">
                                <span class="nav-icon"><LucideIcon name="users" size="20"></LucideIcon></span>
                                <span class="nav-label">Users</span>
                            </button>
                            <button @click="store.setView('cms')" :class="{ active: store.state.currentView === 'cms' }">
                                <span class="nav-icon"><LucideIcon name="file-text" size="20"></LucideIcon></span>
                                <span class="nav-label">CMS</span>
                            </button>
                            <button @click="store.setView('cms-templates')" :class="{ active: store.state.currentView === 'cms-templates' }">
                                <span class="nav-icon"><LucideIcon name="layout-template" size="20"></LucideIcon></span>
                                <span class="nav-label">Templates</span>
                            </button>
                            <button @click="store.setView('forms')" :class="{ active: store.state.currentView === 'forms' }">
                                <span class="nav-icon"><LucideIcon name="clipboard-list" size="20"></LucideIcon></span>
                                <span class="nav-label">Forms</span>
                            </button>
                            <button @click="store.setView('database')" :class="{ active: store.state.currentView === 'database' }">
                                <span class="nav-icon"><LucideIcon name="database" size="20"></LucideIcon></span>
                                <span class="nav-label">Databases</span>
                            </button>
                            <button @click="store.setView('map')" :class="{ active: store.state.currentView === 'map' }">
                                <span class="nav-icon"><LucideIcon name="map" size="20"></LucideIcon></span>
                                <span class="nav-label">Maps</span>
                            </button>
                            <button @click="store.setView('api-builder')" :class="{ active: store.state.currentView === 'api-builder' }">
                                <span class="nav-icon"><LucideIcon name="zap" size="20"></LucideIcon></span>
                                <span class="nav-label">APIs</span>
                            </button>
                        </template>

                        <!-- Member Menu -->
                        <template v-else>
                            <button @click="store.setView('todos')" :class="{ active: store.state.currentView === 'todos' }">
                                <span class="nav-icon"><LucideIcon name="check-square" size="20"></LucideIcon></span>
                                <span class="nav-label">My Todos</span>
                            </button>
                            <button @click="store.setView('cms')" :class="{ active: store.state.currentView === 'cms' }">
                                <span class="nav-icon"><LucideIcon name="file-text" size="20"></LucideIcon></span>
                                <span class="nav-label">CMS</span>
                            </button>
                            <button @click="store.setView('forms')" :class="{ active: store.state.currentView === 'forms' }">
                                <span class="nav-icon"><LucideIcon name="clipboard-list" size="20"></LucideIcon></span>
                                <span class="nav-label">Forms</span>
                            </button>
                            <button @click="store.setView('map')" :class="{ active: store.state.currentView === 'map' }">
                                <span class="nav-icon"><LucideIcon name="map" size="20"></LucideIcon></span>
                                <span class="nav-label">Maps</span>
                            </button>
                        </template>
                        
                        <button @click="store.setView('settings')" :class="{ active: store.state.currentView === 'settings' }" style="opacity: 0.8;">
                            <span class="nav-icon"><LucideIcon name="settings" size="20"></LucideIcon></span>
                            <span class="nav-label">Settings</span>
                        </button>
                        
                        <div class="user-controls">
                            <div class="user-info-item">
                                <span class="nav-icon"><LucideIcon name="user" size="20"></LucideIcon></span>
                                <span class="nav-label">{{ store.state.user.username }}</span>
                            </div>
                            <div class="button-group">
                                <button @click="store.toggleTheme()" class="theme-toggle" :title="'Toggle Theme (' + store.state.theme + ')'">
                                     <LucideIcon :name="store.state.theme === 'dark' ? 'sun' : 'moon'" size="20"></LucideIcon>
                                </button>
                                <button @click="store.logout()" class="logout-btn" :title="'Logout (' + store.state.user.username + ')'">
                                    <LucideIcon name="log-out" size="20"></LucideIcon>
                                </button>
                            </div>
                        </div>
                    </nav>
                    <nav v-else style="display: flex; align-items: center; gap: 10px;">
                        <button @click="store.toggleTheme()" class="theme-toggle" :title="'Toggle Theme (' + store.state.theme + ')'">
                            <LucideIcon :name="store.state.theme === 'dark' ? 'sun' : 'moon'" size="20"></LucideIcon>
                        </button>
                        <button @click="store.setLoginMode('login')" :class="{ active: store.state.loginMode === 'login' }">
                            <span class="nav-icon"><LucideIcon name="log-in" size="20"></LucideIcon></span>
                            <span class="nav-label">Login</span>
                        </button>
                        <button @click="store.setLoginMode('register')" :class="{ active: store.state.loginMode === 'register' }">
                            <span class="nav-icon"><LucideIcon name="user-plus" size="20"></LucideIcon></span>
                            <span class="nav-label">Register</span>
                        </button>
                    </nav>
                </div>
            </header>
            
            <main>
                <div v-if="!store.state.user">
                    <Login @login="onLogin" />
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
        const isAdmin = store.getters.isAdmin;

        const currentComponent = computed(() => {
            if (!store.state.user) return Login;
            switch (store.state.currentView) {
                case 'dashboard': return isAdmin.value ? AdminDashboard : TodoList;
                case 'users': return isAdmin.value ? UsersAdmin : TodoList;
                case 'cms': return CMS;
                case 'cms-templates': return CMS;
                case 'forms': return FormsAdmin;
                case 'database': return isAdmin.value ? DatabaseManager : TodoList;
                case 'map': return MapPanel;
                case 'api-builder': return isAdmin.value ? ApiManager : TodoList;
                case 'settings': return UserSettings;
                case 'todos': default: return TodoList;
            }
        });

        const onLogin = async (loggedInUser) => {
            store.setUser(loggedInUser);
            setDefaultView();
        };

        const setDefaultView = () => {
            if (isAdmin.value) {
                store.setView('dashboard');
            } else {
                store.setView('todos');
            }
        };

        onMounted(async () => {
            // Re-apply stored preferences (DOM side-effects)
            store.setTheme(store.state.theme);
            store.setLayout(store.state.layout);

            const loggedIn = await store.checkSession();
            if (loggedIn) {
                // If we are on login screen but have session, go to default app view
                if (store.state.currentView === 'todos') { // default
                    setDefaultView();
                }
            }
        });

        return {
            store,
            isAdmin,
            currentComponent,
            onLogin
        };
    }
};

createApp(App).mount('#app');
