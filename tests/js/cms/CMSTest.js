import CMS from 'components/cms/CMS.js';
import { describe, it, expect, mount } from '../framework/test-utils.js';

describe('CMS Component', () => {
    it('renders', () => {
        const wrapper = mount(CMS);
        expect(wrapper.element).toBeTruthy();
        // Add more specific assertions if possible, e.g. checking for 'Pages' tab
    });
});
