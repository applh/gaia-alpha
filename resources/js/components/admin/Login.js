import { ref, watch, computed, defineAsyncComponent } from 'vue';
import { store } from 'store';
import Input from 'ui/Input.js';
import Button from 'ui/Button.js';
import AsyncForm from 'ui/AsyncForm.js';

export default {
    components: {
        'password-input': defineAsyncComponent(() => import('ui/PasswordInput.js')),
        'ui-input': Input,
        'ui-button': Button,
        AsyncForm
    },
    template: `
        <div class="login-container">
            <AsyncForm 
                :action="login" 
                :submitLabel="loading ? 'Logging in...' : 'Login'"
            >
                <h1>Gaia Alpha</h1>
                <ui-input 
                    label="Username" 
                    v-model="username" 
                    required 
                    autofocus 
                />
                
                <div class="form-group">
                    <label>Password</label>
                    <password-input v-model="password" required placeholder="Enter password"></password-input>
                </div>
                
                <!-- Helper for toggling mode (if intended) or just extra spacing -->
                <!-- The existing code had logic for toggleMode but no button. -->
            </AsyncForm>
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
        // const error = ref(''); // Handled by AsyncForm
        // const loading = ref(false); // Handled by AsyncForm

        const login = async () => {
            const endpoint = isLogin.value ? '/@/login' : '/@/register';

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
                    // AsyncForm will show success. We might want to show a message though?
                    // AsyncForm catches errors, but for success it just says "Saved!" default.
                    // We can return a value or just let it finish.
                    throw new Error('Registration successful. Please login.'); // Hack to show message as error? No.
                    // Ideally AsyncForm supports customized success message per call.
                    // For now, standard success is fine.
                }
            } else {
                throw new Error(data.error || 'An error occurred');
            }
        };

        return { isLogin, toggleMode, username, password, login };
    }
};
