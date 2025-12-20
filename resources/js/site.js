import { createApp, defineAsyncComponent, onMounted, computed, ref } from 'vue';
import { store } from './store.js';

const Login = defineAsyncComponent(() => import('components/admin/Login.js'));
const TodoList = defineAsyncComponent(() => import('plugins/Todo/TodoList.js'));
const AdminDashboard = defineAsyncComponent(() => import('components/admin/AdminDashboard.js'));
const UsersAdmin = defineAsyncComponent(() => import('components/admin/settings/UsersAdmin.js'));
const CMS = defineAsyncComponent(() => import('components/cms/CMS.js'));
const DatabaseManager = defineAsyncComponent(() => import('plugins/DatabaseManager/DatabaseManager.js'));
const UserSettings = defineAsyncComponent(() => import('components/admin/settings/UserSettings.js'));
const SiteSettings = defineAsyncComponent(() => import('components/admin/settings/SiteSettings.js'));
const FormsAdmin = defineAsyncComponent(() => import('components/cms/FormsAdmin.js'));
const ApiManager = defineAsyncComponent(() => import('plugins/ApiBuilder/ApiManager.js'));
const MapPanel = defineAsyncComponent(() => import('plugins/Map/MapPanel.js'));
const ConsolePanel = defineAsyncComponent(() => import('plugins/Console/ConsolePanel.js'));
const ChatPanel = defineAsyncComponent(() => import('plugins/Chat/ChatPanel.js'));
const MultiSitePanel = defineAsyncComponent(() => import('plugins/MultiSite/MultiSitePanel.js'));
const ComponentBuilder = defineAsyncComponent(() => import('plugins/ComponentBuilder/ComponentBuilder.js'));
const MailPanel = defineAsyncComponent(() => import('plugins/Mail/MailPanel.js'));
const PluginsAdmin = defineAsyncComponent(() => import('components/admin/settings/PluginsAdmin.js'));
const JwtSettings = defineAsyncComponent(() => import('plugins/JwtAuth/JwtSettings.js'));

const ToastContainer = {
    setup() {
        onMounted(() => {
            if (!document.getElementById('toast-styles')) {
                const style = document.createElement('style');
                style.id = 'toast-styles';
                style.textContent = `
                    .toast-container {
                        position: fixed;
                        top: 20px;
                        left: 20px;
                        z-index: 2147483647;
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                        pointer-events: none;
                    }
                    .toast {
                        pointer-events: auto;
                        background: var(--bg-card);
                        color: var(--text-main);
                        border: 1px solid var(--border-color);
                        border-left: 4px solid var(--primary-color);
                        padding: 12px 16px;
                        border-radius: 6px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                        display: flex;
                        align-items: flex-start;
                        gap: 12px;
                        min-width: 300px;
                        max-width: 400px;
                        animation: slideIn 0.3s ease;
                    }
                    .toast.success { 
                        background: var(--success-color, #10b981); 
                        color: #fff; 
                        border-left: none; 
                        border: 1px solid rgba(255,255,255,0.1);
                    }
                    .toast.error { 
                        background: var(--error-color, #ef4444); 
                        color: #fff; 
                        border-left: none;
                        border: 1px solid rgba(255,255,255,0.1);
                    }
                    .toast.info { 
                        background: var(--info-color, #3b82f6); 
                        color: #fff; 
                        border-left: none;
                        border: 1px solid rgba(255,255,255,0.1);
                    }
                    
                    /* Adjust close button for colored backgrounds */
                    .toast.success .toast-close,
                    .toast.error .toast-close,
                    .toast.info .toast-close {
                        color: rgba(255,255,255,0.8);
                    }
                    .toast.success .toast-close:hover,
                    .toast.error .toast-close:hover,
                    .toast.info .toast-close:hover {
                        color: #fff;
                    }
                    
                    .toast-message {
                        flex: 1;
                        font-size: 0.95rem;
                        line-height: 1.4;
                    }
                    .toast-close {
                        background: none;
                        border: none;
                        color: var(--text-muted);
                        font-size: 1.2rem;
                        line-height: 1;
                        cursor: pointer;
                        padding: 0;
                        opacity: 0.7;
                    }
                    .toast-close:hover { opacity: 1; }

                    @keyframes slideIn {
                        from { transform: translateX(-100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    .toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
                    .toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-30px); }
                `;
                document.head.appendChild(style);
            }
        });
        return { store };
    },
    template: `
        <div class="toast-container">
            <transition-group name="toast">
                <div v-for="note in store.state.notifications" :key="note.id" class="toast" :class="note.type">
                    <div class="toast-message">{{ note.message }}</div>
                    <button class="toast-close" @click="store.removeNotification(note.id)">×</button>
                </div>
            </transition-group>
        </div>
    `,
    // styles injected in setup
};

const App = {
    components: { Login, ToastContainer, LucideIcon: defineAsyncComponent(() => import('ui/Icon.js')) },
    template: `
        <div class="app-container">
            <ToastContainer />
            <header>
                <a href="/" style="text-decoration: none; color: inherit; display:flex; align-items:center;">
                    <img v-if="siteLogo" :src="siteLogo" :alt="siteTitle" style="height: 28px; margin-right: 10px;">
                    <h1 v-else>{{ siteTitle }}</h1>
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
        const siteTitle = (window.siteConfig && window.siteConfig.site_title) ? window.siteConfig.site_title : 'Gaia Alpha';
        const siteLogo = (window.siteConfig && window.siteConfig.site_logo) ? window.siteConfig.site_logo : null;
        const isAdmin = store.getters.isAdmin;
        const activeDropdown = ref(null);
        const isMobile = ref(window.innerWidth <= 768);

        window.addEventListener('resize', () => {
            isMobile.value = window.innerWidth <= 768;
        });

        const baseMenuItems = [
            { label: 'Dashboard', view: 'dashboard', icon: 'layout-dashboard', adminOnly: true },
            {
                label: 'Projects', icon: 'check-square', id: 'grp-projects', children: [
                    // { label: 'Tasks', view: 'todos', icon: 'list-todo' }, // Moved to Plugin
                ]
            },
            {
                label: 'Content', icon: 'folder', id: 'grp-content', children: [
                    { label: 'CMS', view: 'cms', icon: 'file-text' },
                    { label: 'Templates', view: 'cms-templates', icon: 'layout-template', adminOnly: true },
                    // { label: 'Components', view: 'cms-components', icon: 'puzzle', adminOnly: true }, // Injected by Plugin
                    { label: 'Forms', view: 'forms', icon: 'clipboard-list' }
                ]
            },
            {
                label: 'System', icon: 'settings-2', id: 'grp-system', adminOnly: true, children: [
                    { label: 'Users', view: 'users', icon: 'users' },

                    // APIs injected via plugin
                    // Console injected via plugin
                    // Sites injected via plugin
                    { label: 'Site Settings', view: 'site-settings', icon: 'globe' },
                    { label: 'Plugins', view: 'plugins', icon: 'plug' },
                ]
            }
        ];

        const menuTree = computed(() => {
            const admin = isAdmin.value;
            const dynamicItems = (store.state.user && store.state.user.menu_items) ? store.state.user.menu_items : [];

            // Validation helper
            const validateMenuItem = (item) => {
                if (!item.label && !item.id) {
                    console.warn('Invalid menu item (missing label or id):', item);
                    return false;
                }
                if (!item.id && !item.view && (!item.children || item.children.length === 0)) {
                    console.warn('Invalid menu item (missing view, id, or children):', item);
                    return false;
                }
                return true;
            };

            // Deep clone base items to avoid mutation issues
            let items = JSON.parse(JSON.stringify(baseMenuItems));

            // Merge dynamic items
            dynamicItems.forEach(dItem => {
                if (!validateMenuItem(dItem)) return;

                const existingIndex = items.findIndex(i => i.id === dItem.id || (i.label && i.label === dItem.label));
                if (existingIndex > -1) {
                    // Merge children
                    if (dItem.children) {
                        if (!items[existingIndex].children) items[existingIndex].children = [];
                        items[existingIndex].children.push(...dItem.children.filter(validateMenuItem));
                    }
                } else {
                    // Add new item
                    items.push(dItem);
                }
            });

            return items.map(item => {
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
                case 'dashboard': return isAdmin.value ? AdminDashboard : TodoList; // Fallback to TodoList for non-admin on dashboard? Should probably be dynamic too.
                case 'users': return isAdmin.value ? UsersAdmin : TodoList;
                case 'cms': return CMS;
                case 'cms-templates': return CMS;
                case 'cms-components': return CMS;
                case 'forms': return FormsAdmin;
                case 'database': return isAdmin.value ? DatabaseManager : TodoList;
                case 'map': return MapPanel;
                case 'api-builder': return isAdmin.value ? ApiManager : TodoList;
                case 'console': return isAdmin.value ? ConsolePanel : TodoList;
                case 'chat': return ChatPanel;
                case 'settings': return UserSettings;
                case 'site-settings': return SiteSettings;
                case 'sites': return isAdmin.value ? MultiSitePanel : TodoList;
                case 'component-builder': return isAdmin.value ? ComponentBuilder : TodoList;
                case 'mail/inbox': return isAdmin.value ? MailPanel : TodoList;
                case 'plugins': return isAdmin.value ? PluginsAdmin : TodoList;
                case 'jwt-settings': return isAdmin.value ? JwtSettings : TodoList;

                case 'todos': return TodoList;
            }
            // Check for custom component
            if (customComponents.value[store.state.currentView]) {
                return customComponents.value[store.state.currentView];
            }

            // Default / Not Found
            return {
                template: `<div class="admin-page"><div class="admin-card"><h2>Page Not Found</h2><p>The requested view "{{view}}" does not exist or you do not have permission.</p></div></div>`,
                setup() { return { view: store.state.currentView } }
            };
        });

        const customComponents = ref({});
        const loadCustomComponents = async () => {
            if (!isAdmin.value) return;

            // Check if ComponentBuilder plugin is active
            const activePlugins = (window.siteConfig && window.siteConfig.active_plugins) ? window.siteConfig.active_plugins : [];
            if (!activePlugins.includes('ComponentBuilder')) return;

            try {
                const res = await fetch('/@/admin/component-builder/list');
                const components = await res.json();
                if (Array.isArray(components)) {
                    components.forEach(comp => {
                        // Dynamic import with Vite/Webpack requires some static part of path
                        // We assume the generated file is at ./components/custom/{viewName}.js
                        // Note: view_name in DB should match filename.
                        // We use name for filename usually.
                        const name = comp.name.replace(/[^a-zA-Z0-9_-]/g, '');
                        if (comp.enabled) {
                            customComponents.value[comp.view_name] = defineAsyncComponent(
                                () => import(`./components/custom/${name}.js`)
                            );

                            // Add to menu if not exists
                            // Ideally we should merge into menuTree but simple append logic for now
                            // We don't modify menuItems directly as it's static const
                            // We'll handle this by pushing to a reactive array if we made menuItems reactive
                            // For now, let's just make the component available for routing.
                        }
                    });
                }
            } catch (e) {
                console.error('Failed to load custom components', e);
            }
        };

        const onLogin = async (loggedInUser) => {
            store.setUser(loggedInUser);
            // Only set default if current view is the default 'todos' or 'login' state (respect deep links)
            if (!store.state.currentView || store.state.currentView === 'todos' || store.state.currentView === 'login') {
                setDefaultView();
            }
        };

        const setDefaultView = () => {
            if (isAdmin.value) {
                store.setView('dashboard');
                return;
            }

            // Fallback to first available item in menu
            if (menuTree.value.length > 0) {
                const first = menuTree.value[0];
                if (first.children && first.children.length > 0) {
                    store.setView(first.children[0].view);
                } else {
                    store.setView(first.view);
                }
            } else {
                store.setView('settings'); // Absolute fallback if menu is empty
            }
        };

        onMounted(async () => {
            // Re-apply stored preferences (DOM side-effects)
            store.setTheme(store.state.theme);
            store.setLayout(store.state.layout);

            const loggedIn = await store.checkSession();
            if (loggedIn) {
                // If we are on login screen but have session, go to default app view
                if (!store.state.currentView || store.state.currentView === 'todos' || store.state.currentView === 'login') {
                    setDefaultView();
                }

                // Load custom components
                await loadCustomComponents();
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
            isMobile,
            siteTitle,
            siteLogo
        };
    }
};

createApp(App).mount('#app');
