import Login from 'components/admin/Login.js';
import { describe, it, expect, mount, flushPromises } from '../framework/test-utils.js';
import { store } from 'store';

describe('Login Component', () => {
    it('renders login form by default', async () => {
        // Mock fetch
        const originalFetch = window.fetch;
        window.fetch = async () => ({
            ok: true,
            json: async () => ({ status: 'ok', user: { id: 1, username: 'admin', level: 100 } })
        });

        store.setLoginMode('login');
        const wrapper = mount(Login);
        await flushPromises();
        // Wait for async components like PasswordInput
        await flushPromises();

        // Helper to wait for async component
        await new Promise(r => setTimeout(r, 100));
        await flushPromises();

        expect(wrapper.text()).toContain('Gaia Alpha');

        // Check for password input - it might be inside the shadow or just nested
        const passwordInput = wrapper.find('input[type="password"]');
        expect(passwordInput).toBeTruthy();

        window.fetch = originalFetch;
    });

    it('renders register form when mode is register', async () => {
        store.setLoginMode('register');
        const wrapper = mount(Login);
        await flushPromises();
        await flushPromises();

        expect(wrapper.text()).toContain('Gaia Alpha');
    });
});
