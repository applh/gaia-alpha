import { createApp, ref, defineAsyncComponent, onMounted } from 'vue';

const Login = defineAsyncComponent(() => import('./components/Login.js'));
const TodoList = defineAsyncComponent(() => import('./components/TodoList.js'));

const app = createApp({
    components: { Login, TodoList },
    setup() {
        const user = ref(null);
        const loading = ref(true);

        const checkAuth = async () => {
            try {
                const res = await fetch('/api/user');
                const data = await res.json();
                user.value = data.user;
            } catch (e) {
                user.value = null;
            } finally {
                loading.value = false;
            }
        };

        const onLogin = (loggedInUser) => {
            user.value = loggedInUser;
        };

        const logout = async () => {
            await fetch('/api/logout', { method: 'POST' });
            user.value = null;
        };

        onMounted(checkAuth);

        return { user, loading, onLogin, logout };
    },
    template: `
        <div v-if="!loading">
            <header v-if="user">
                <span>Welcome, {{ user.username }}</span>
                <button @click="logout">Logout</button>
            </header>
            <main>
                <TodoList v-if="user" />
                <Login v-else @login="onLogin" />
            </main>
        </div>
        <div v-else>Loading...</div>
    `
});

app.mount('#app');
