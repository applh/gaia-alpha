import UserSettings from 'components/admin/settings/UserSettings.js';
import { describe, it, expect, mount } from '../../framework/test-utils.js';

describe('UserSettings Component', () => {
    it('renders', () => {
        const wrapper = mount(UserSettings);
        expect(wrapper.element).toBeTruthy();
        expect(wrapper.text()).toContain('Settings'); // or Profile
    });
});
