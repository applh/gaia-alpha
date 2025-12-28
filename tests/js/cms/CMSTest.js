import CMS from 'components/cms/CMS.js';
import { describe, it, expect, mount, flushPromises } from '../framework/test-utils.js';

describe('CMS Component', () => {
    it('renders', async () => {
        // Mock fetch
        const originalFetch = window.fetch;
        window.fetch = async () => ({
            ok: true,
            json: async () => ({ pages: [], templates: [] })
        });

        const wrapper = mount(CMS);
        await flushPromises();

        expect(wrapper.element).toBeTruthy();

        window.fetch = originalFetch;
    });
});
