import { ref, watch, computed } from 'vue';
import { store } from '../store.js';

export default {
    template: `
        <div class="auth-container">
            <h2>{{ isLogin ? 'Login' : 'Register' }}</h2>
            <form @submit.prevent="handleSubmit">
                <div class="form-group">
                    <label>Username:</label>
                    <input v-model="username" type="text" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input v-model="password" type="password" required>
                </div>
                <button type="submit">{{ isLogin ? 'Login' : 'Register' }}</button>
            </form>
            <p>
                {{ isLogin ? 'Need an account?' : 'Already have an account?' }}
                <a href="#" @click.prevent="toggleMode">
                    {{ isLogin ? 'Register' : 'Login' }}
                </a>
            </p>
            <p v-if="error" class="error">{{ error }}</p>
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

        const handleSubmit = async () => {
            error.value = '';
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
            }
        };

        return { isLogin, toggleMode, username, password, error, handleSubmit };
    }
};
