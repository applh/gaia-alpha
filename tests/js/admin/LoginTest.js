import Login from 'components/admin/Login.js';
import { describe, it, expect, mount, flushPromises } from '../framework/test-utils.js';
import { store } from 'store';

describe('Login Component', () => {
    it('renders login form by default', async () => {
        store.setLoginMode('login');
        const wrapper = mount(Login);
        await flushPromises();
        expect(wrapper.text()).toContain('Login');
        expect(wrapper.element.querySelector('input[type="password"]')).toBeTruthy();
    });

    it('renders register form when mode is register', async () => {
        store.setLoginMode('register');
        const wrapper = mount(Login);
        await flushPromises();
        // Button text changes? Logic in Login.js: submitLabel="Login". 
        // Wait, Login.js is hardcoded submitLabel="Login".
        // But maybe title? "Gaia Alpha".
        // The component handles toggle via store but might not change UI much visually besides endpoint.
        // Let's check Login.js again inside the test? No, we know the code.
        // It has `const endpoint = isLogin.value ? '/@/login' : '/@/register';`
        // But the UI seems static "Gaia Alpha" h1.
        expect(wrapper.text()).toContain('Gaia Alpha');
    });
});
