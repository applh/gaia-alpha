import UsersAdmin from 'components/admin/settings/UsersAdmin.js';
import { describe, it, expect, mount } from '../../framework/test-utils.js';

describe('UsersAdmin Component', () => {
    it('renders', async () => {
        const originalFetch = window.fetch;
        window.fetch = async () => ({
            ok: true,
            json: async () => ({ users: [] })
        });

        const wrapper = mount(UsersAdmin);
        // await flushPromises(); 
        // Not strictly needed if we just check for title "Users" which is static
        expect(wrapper.element).toBeTruthy();
        expect(wrapper.text()).toContain('User Management');

        window.fetch = originalFetch;
    });
});
