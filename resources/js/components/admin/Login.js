import { ref, watch, computed, defineAsyncComponent } from 'vue';
import { store } from 'store';
import { api } from 'api';
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
                submitLabel="Login"
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
            const path = isLogin.value ? 'login' : 'register';

            try {
                const data = await api.post(path, { username: username.value, password: password.value });

                if (isLogin.value) {
                    emit('login', data);
                } else {
                    store.setLoginMode('login');
                    // AsyncForm will show success
                }
            } catch (e) {
                throw new Error(e.message || 'An error occurred');
            }
        };

        return { isLogin, toggleMode, username, password, login };
    }
};
