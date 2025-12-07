import { ref, watch } from 'vue';

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
                <a href="#" @click.prevent="isLogin = !isLogin">
                    {{ isLogin ? 'Register' : 'Login' }}
                </a>
            </p>
            <p v-if="error" class="error">{{ error }}</p>
        </div>
    `,
    props: ['mode'],
    setup(props, { emit }) {
        const isLogin = ref(props.mode === 'login');

        // Update local state when prop changes
        watch(() => props.mode, (newMode) => {
            isLogin.value = newMode === 'login';
        });

        const username = ref('');
        const password = ref('');
        const error = ref('');

        const handleSubmit = async () => {
            error.value = '';
            const endpoint = isLogin.value ? '/api/login' : '/api/register';
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
                        // Auto login or ask to login
                        isLogin.value = true;
                        error.value = 'Registration successful. Please login.';
                    }
                } else {
                    error.value = data.error || 'An error occurred';
                }
            } catch (e) {
                error.value = 'Network error';
            }
        };

        return { isLogin, username, password, error, handleSubmit };
    }
};
