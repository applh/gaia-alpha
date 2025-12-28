import SiteSettings from 'components/admin/settings/SiteSettings.js';
import { describe, it, expect, mount, flushPromises } from '../../framework/test-utils.js';

describe('SiteSettings Component', () => {
    it('renders', async () => {
        const originalFetch = window.fetch;
        window.fetch = async () => ({
            ok: true,
            json: async () => ({
                site_title: 'Test Site',
                admin_email: 'admin@test.com',
                maintenance_mode: 0
            })
        });

        const wrapper = mount(SiteSettings);
        await flushPromises();

        expect(wrapper.element).toBeTruthy();
        expect(wrapper.text()).toContain('Site Settings');

        window.fetch = originalFetch;
    });
});
