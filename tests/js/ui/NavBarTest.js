import NavBar from 'components/ui/NavBar.js';
import { describe, it, expect, mount } from '../framework/test-utils.js';
import { store } from 'store';

describe('NavBar', () => {
    it('renders with user logged in', async () => {
        store.setUser({ username: 'testuser', role: 'admin' });

        const wrapper = mount(NavBar);
        // Wait for potential async defineAsyncComponent
        await new Promise(r => setTimeout(r, 100));

        expect(wrapper.text()).toContain('testuser');
    });

    it('renders login/register when no user', async () => {
        store.setUser(null);

        const wrapper = mount(NavBar);
        await new Promise(r => setTimeout(r, 100));

        expect(wrapper.text()).toContain('Login');
        expect(wrapper.text()).toContain('Register');
    });
});
