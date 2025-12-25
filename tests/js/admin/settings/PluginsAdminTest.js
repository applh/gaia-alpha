import PluginsAdmin from 'components/admin/settings/PluginsAdmin.js';
import { describe, it, expect, mount } from '../../framework/test-utils.js';

describe('PluginsAdmin Component', () => {
    it('renders', () => {
        const wrapper = mount(PluginsAdmin);
        expect(wrapper.element).toBeTruthy();
        expect(wrapper.text()).toContain('Plugins');
    });
});
