import AdminDashboard from 'components/admin/AdminDashboard.js';
import { describe, it, expect, mount, flushPromises } from '../framework/test-utils.js';

describe('Admin Dashboard', () => {
    it('renders loading state initially', () => {
        const wrapper = mount(AdminDashboard);
        expect(wrapper.text()).toContain('Loading stats...');
    });

    it('renders stats after fetch', async () => {
        const originalFetch = window.fetch;
        window.fetch = async (url) => {
            if (url.includes('/@/admin/stats')) {
                return {
                    ok: true,
                    json: async () => ({
                        cards: [
                            { label: 'Total Users', value: 42, icon: 'users' }
                        ]
                    })
                };
            }
            return { ok: false };
        };

        const wrapper = mount(AdminDashboard);
        await flushPromises(); // Wait for onMounted fetch
        // await new Promise(r => setTimeout(r, 50)); // Extra wait for reactivity

        expect(wrapper.text()).toContain('Total Users');
        expect(wrapper.text()).toContain('42');

        window.fetch = originalFetch;
    });
});
