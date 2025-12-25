import SiteSettings from 'components/admin/settings/SiteSettings.js';
import { describe, it, expect, mount } from '../../framework/test-utils.js';

describe('SiteSettings Component', () => {
    it('renders', () => {
        const wrapper = mount(SiteSettings);
        expect(wrapper.element).toBeTruthy();
        expect(wrapper.text()).toContain('Site Settings');
    });
});
