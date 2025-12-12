import { createApp, defineAsyncComponent, onMounted, computed, ref } from 'vue';
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
const ConsolePanel = defineAsyncComponent(() => import('./components/ConsolePanel.js'));

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
                        
                        <!-- Dynamic Menu Generation -->
                        <template v-for="item in menuTree" :key="item.id || item.view">
                            
                            <!-- Single Item -->
                            <button v-if="!item.children" 
                                @click="store.setView(item.view)" 
                                :class="{ active: store.state.currentView === item.view }">
                                <span class="nav-icon"><LucideIcon :name="item.icon" size="20"></LucideIcon></span>
                                <span class="nav-label">{{ item.label }}</span>
                            </button>

                            <!-- Dropdown Group -->
                            <div v-else class="nav-group" 
                                :class="{ 'open': activeDropdown === item.id, 'active': isGroupActive(item) }"
                                @mouseenter="!isMobile && (activeDropdown = item.id)"
                                @mouseleave="!isMobile && (activeDropdown = null)"
                                @click="isMobile && toggleDropdown(item.id)">
                                
                                <button class="nav-group-trigger" :class="{ 'group-active': isGroupActive(item) }">
                                    <span class="nav-icon"><LucideIcon :name="item.icon" size="20"></LucideIcon></span>
                                    <span class="nav-label">{{ item.label }}</span>
                                    <span class="chevron"><LucideIcon name="chevron-down" size="14"></LucideIcon></span>
                                </button>
                                
                                <div class="nav-dropdown-menu">
                                    <button v-for="child in item.children" 
                                        :key="child.view"
                                        @click.stop="store.setView(child.view); activeDropdown = null"
                                        :class="{ active: store.state.currentView === child.view }">
                                        <span class="nav-icon"><LucideIcon :name="child.icon" size="18"></LucideIcon></span>
                                        <span class="nav-label">{{ child.label }}</span>
                                    </button>
                                </div>
                            </div>

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
        const activeDropdown = ref(null);
        const isMobile = ref(window.innerWidth <= 768);

        window.addEventListener('resize', () => {
            isMobile.value = window.innerWidth <= 768;
        });

        const menuItems = [
            { label: 'Dashboard', view: 'dashboard', icon: 'layout-dashboard', adminOnly: true },
            { label: 'Projects', view: 'todos', icon: 'check-square' },
            {
                label: 'Content', icon: 'folder', id: 'grp-content', children: [
                    { label: 'CMS', view: 'cms', icon: 'file-text' },
                    { label: 'Templates', view: 'cms-templates', icon: 'layout-template', adminOnly: true },
                    { label: 'Forms', view: 'forms', icon: 'clipboard-list' },
                    { label: 'Maps', view: 'map', icon: 'map' }
                ]
            },
            {
                label: 'System', icon: 'settings-2', id: 'grp-system', adminOnly: true, children: [
                    { label: 'Users', view: 'users', icon: 'users' },
                    { label: 'Databases', view: 'database', icon: 'database' },
                    { label: 'APIs', view: 'api-builder', icon: 'zap' },
                    { label: 'Console', view: 'console', icon: 'terminal' }
                ]
            }
        ];

        const menuTree = computed(() => {
            const admin = isAdmin.value;
            return menuItems.map(item => {
                if (item.adminOnly && !admin) return null;

                if (item.children) {
                    const visibleChildren = item.children.filter(child => !child.adminOnly || admin);
                    if (visibleChildren.length === 0) return null;
                    return { ...item, children: visibleChildren };
                }
                return item;
            }).filter(Boolean);
        });

        const isGroupActive = (item) => {
            if (!item.children) return false;
            return item.children.some(child => child.view === store.state.currentView);
        };

        const toggleDropdown = (id) => {
            if (activeDropdown.value === id) {
                activeDropdown.value = null;
            } else {
                activeDropdown.value = id;
            }
        };

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
                case 'console': return isAdmin.value ? ConsolePanel : TodoList;
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
            onLogin,
            menuTree,
            activeDropdown,
            toggleDropdown,
            isGroupActive,
            isMobile
        };
    }
};

createApp(App).mount('#app');
