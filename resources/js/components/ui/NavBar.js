
import { defineAsyncComponent, ref, onUnmounted } from 'vue';
import { store } from '../../store.js';
import { useMenu } from '../../composables/useMenu.js';

export default {
    components: { LucideIcon: defineAsyncComponent(() => import('ui/Icon.js')) },
    setup() {
        const { menuTree } = useMenu();

        const activeDropdown = ref(null);
        const isMobile = ref(window.innerWidth <= 768);

        const onResize = () => {
            isMobile.value = window.innerWidth <= 768;
        };

        const closeDropdown = (e) => {
            if (activeDropdown.value && !e.target.closest('.nav-group')) {
                activeDropdown.value = null;
            }
        };

        window.addEventListener('resize', onResize);
        window.addEventListener('click', closeDropdown);

        onUnmounted(() => {
            window.removeEventListener('resize', onResize);
            window.removeEventListener('click', closeDropdown);
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

        return {
            store,
            menuTree,
            activeDropdown,
            isMobile,
            isGroupActive,
            toggleDropdown
        };
    },
    template: `
        <template v-if="store.state.user">
            <nav style="display: flex; align-items: center; gap: 10px;">
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
                        @click="toggleDropdown(item.id)">
                        
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
        </template>
        <template v-else>
            <nav style="display: flex; align-items: center; gap: 10px;">
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
        </template>
    `
};
