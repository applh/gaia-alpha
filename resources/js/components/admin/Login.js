import { ref, watch, computed, defineAsyncComponent } from 'vue';
import { store } from 'store';

export default {
    components: {
        'password-input': defineAsyncComponent(() => import('ui/PasswordInput.js'))
    },
    template: `
        <div class="login-container">
            <form @submit.prevent="login">
                <h1>Gaia Alpha</h1>
                <div class="form-group">
                    <label>Username</label>
                    <input v-model="username" type="text" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <password-input v-model="password" required placeholder="Enter password"></password-input>
                </div>
                <div class="error" v-if="error">{{ error }}</div>
                <button type="submit" :disabled="loading">
                    {{ loading ? 'Logging in...' : 'Login' }}
                </button>
            </form>
        </div>
    `,
    setup(props, { emit }) {
        // Use computed writable or sync manually
        const isLogin = computed({
            get: () => store.state.loginMode === 'login',
            set: (val) => store.setLoginMode(val ? 'login' : 'register')
        });

        const toggleMode = () => {
            store.setLoginMode(isLogin.value ? 'register' : 'login');
        };

        const username = ref('');
        const password = ref('');
        const error = ref('');
        const loading = ref(false);

        const login = async () => {
            error.value = '';
            loading.value = true;
            const endpoint = isLogin.value ? '/@/login' : '/@/register';
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: username.value, password: password.value })
                });
                const data = await response.json();

                if (response.ok) {
                    if (isLogin.value) {
                        emit('login', data.user);
                    } else {
                        // Switch to login mode after successful register
                        store.setLoginMode('login');
                        error.value = 'Registration successful. Please login.';
                    }
                } else {
                    error.value = data.error || 'An error occurred';
                }
            } catch (e) {
                error.value = 'Network error';
            } finally {
                loading.value = false;
            }
        };

        return { isLogin, toggleMode, username, password, error, loading, login };
    }
};
