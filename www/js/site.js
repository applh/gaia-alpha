import { createApp, ref, defineAsyncComponent, onMounted, computed } from 'vue';

const Login = defineAsyncComponent(() => import('./components/Login.js'));
const TodoList = defineAsyncComponent(() => import('./components/TodoList.js'));
const AdminDashboard = defineAsyncComponent(() => import('./components/AdminDashboard.js'));
const UsersAdmin = defineAsyncComponent(() => import('./components/UsersAdmin.js'));
const CMS = defineAsyncComponent(() => import('./components/CMS.js'));
const DatabaseManager = defineAsyncComponent(() => import('./components/DatabaseManager.js'));

const App = {
    components: { Login },
    template: `
        <div class="app-container">
            <header>
                <a href="/" style="text-decoration: none; color: inherit;">
                    <h1>Gaia Alpha</h1>
                </a>
                <nav v-if="user">
                    <!-- Admin Menu -->
                    <template v-if="isAdmin">
                        <button @click="setView('dashboard')" :class="{ active: currentView === 'dashboard' }">Dashboard</button>
                        <button @click="setView('todos')" :class="{ active: currentView === 'todos' }">My Todos</button>
                        <button @click="setView('users')" :class="{ active: currentView === 'users' }">Users</button>
                        <button @click="setView('cms')" :class="{ active: currentView === 'cms' }">CMS</button>
                        <button @click="setView('database')" :class="{ active: currentView === 'database' }">Database</button>
                    </template>

                    <!-- Member Menu -->
                    <template v-else>
                        <button @click="setView('todos')" :class="{ active: currentView === 'todos' }">My Todos</button>
                        <button @click="setView('cms')" :class="{ active: currentView === 'cms' }">CMS</button>
                    </template>

                    <button @click="logout" class="logout-btn">Logout ({{ user.username }})</button>
                </nav>
                <nav v-else>
                    <button @click="setLoginMode('login')" :class="{ active: loginMode === 'login' }">Login</button>
                    <button @click="setLoginMode('register')" :class="{ active: loginMode === 'register' }">Register</button>
                </nav>
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

        const isAdmin = computed(() => user.value && user.value.level >= 100);

        const currentComponent = computed(() => {
            if (!user.value) return Login;
            switch (currentView.value) {
                case 'dashboard': return isAdmin.value ? AdminDashboard : TodoList;
                case 'users': return isAdmin.value ? UsersAdmin : TodoList;
                case 'cms': return CMS;
                case 'database': return isAdmin.value ? DatabaseManager : TodoList;
                case 'todos': default: return TodoList;
            }
        });

        const checkAuth = async () => {
            try {
                const res = await fetch('/api/user');
                const data = await res.json();
                user.value = data.user;
                if (user.value) setDefaultView();
            } catch (e) {
                console.error("Auth check failed", e);
            }
        };

        const onLogin = (loggedInUser) => {
            user.value = loggedInUser;
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

        onMounted(checkAuth);

        return { user, onLogin, logout, currentView, setView, isAdmin, currentComponent, loginMode, setLoginMode };
    }
};

createApp(App).mount('#app');
