import { createApp, defineAsyncComponent, onMounted, computed, ref } from 'vue';
import { store } from './store.js';
import { useMenu } from './composables/useMenu.js';

const ToastContainer = defineAsyncComponent(() => import('components/ui/ToastContainer.js'));
const NavBar = defineAsyncComponent(() => import('components/ui/NavBar.js'));

// --- Static Imports (Can be migrated to UiManager progressively) ---
const Login = defineAsyncComponent(() => import('components/admin/Login.js'));
const TodoList = defineAsyncComponent(() => import('plugins/Todo/TodoList.js'));
const AdminDashboard = defineAsyncComponent(() => import('components/admin/AdminDashboard.js'));
const UsersAdmin = defineAsyncComponent(() => import('components/admin/settings/UsersAdmin.js'));
const CMS = defineAsyncComponent(() => import('components/cms/CMS.js'));
const PluginsAdmin = defineAsyncComponent(() => import('components/admin/settings/PluginsAdmin.js'));
// FormsAdmin migrated to plugin
const UserSettings = defineAsyncComponent(() => import('components/admin/settings/UserSettings.js'));
const SiteSettings = defineAsyncComponent(() => import('components/admin/settings/SiteSettings.js'));
// MlPca migrated to dynamic loading




const App = {
    components: { Login, ToastContainer, NavBar, LucideIcon: defineAsyncComponent(() => import('ui/Icon.js')) },
    template: `
        <div class="app-container">
            <ToastContainer />
            <header>
                <a href="/" style="text-decoration: none; color: inherit; display:flex; align-items:center;">
                    <img v-if="siteLogo" :src="siteLogo" :alt="siteTitle" style="height: 28px; margin-right: 10px;">
                    <h1 v-else>{{ siteTitle }}</h1>
                </a>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <NavBar />
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

        const { menuTree } = useMenu();

        const currentComponent = computed(() => {
            if (!store.state.user) return Login;

            // 1. Static Map (Legacy)
            const map = {
                'dashboard': isAdmin.value ? AdminDashboard : TodoList,
                'users': isAdmin.value ? UsersAdmin : TodoList,
                'cms': CMS,
                'cms-templates': CMS,
                'cms-components': CMS,
                // 'forms': FormsAdmin, // Handled by plugin
                'settings': UserSettings,
                'site-settings': SiteSettings,
                'plugins': isAdmin.value ? PluginsAdmin : TodoList,


                'todos': TodoList
            };

            // 2. Dynamic Plugin Components (from backend registration)
            if (window.siteConfig && window.siteConfig.ui_components) {
                Object.entries(window.siteConfig.ui_components).forEach(([key, config]) => {
                    if (config.adminOnly && !isAdmin.value) {
                        map[key] = TodoList;
                    } else {
                        // Note: We use dynamic import() here. Browser must support it and module path must be resolveable.
                        map[key] = defineAsyncComponent(() => import(config.path));
                    }
                });
            }

            // 3. Custom Builder Components
            if (customComponents.value) {
                Object.assign(map, customComponents.value);
            }

            if (map[store.state.currentView]) {
                return map[store.state.currentView];
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
            siteTitle,
            siteLogo
        };
    }
};

createApp(App).mount('#app');
