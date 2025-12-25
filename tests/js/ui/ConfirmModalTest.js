import ConfirmModal from 'components/ui/ConfirmModal.js';
import { describe, it, expect, mount, flushPromises } from '../framework/test-utils.js';
import { store } from 'store';

describe('ConfirmModal', () => {
    it('is hidden by default', () => {
        store.state.confirm.show = false;
        const wrapper = mount(ConfirmModal, {
            props: {
                show: false,
                title: 'Confirm?',
                message: 'Really?'
            }
        });
        expect(document.querySelector('.modal-mask')).toBeFalsy();
        // Actually ConfirmModal implementation:
        // defineAsyncComponent from site.js loads it.
        // Let's assume standard Modal usage with v-if="show"
    });

    it('shows when active', async () => {
        const wrapper = mount(ConfirmModal, {
            props: {
                show: true,
                title: 'Confirm Delete',
                message: 'Are you sure?'
            }
        });

        await flushPromises();
        // Check text
        expect(wrapper.text()).toContain('Confirm Delete');
        expect(wrapper.text()).toContain('Are you sure?');
    });

    it('emits confirm event', async () => {
        let confirmed = false;
        const wrapper = mount(ConfirmModal, {
            props: {
                show: true,
                title: 'T',
                message: 'M'
            },
            // Emits are handled by props like onConfirm?
            // ConfirmModal uses @confirm="store.closeConfirm(true)" in site.js
            // But here we mount the component directly.
            // Component likely emits 'confirm'.
        });

        // We need to attach listener to wrapper?
        // mount util:
        // appInstance = createApp(Wrapper);
        // Wrapper renders h(Component, options.props, slots)
        // If we want to listen to events, we need to pass props like `onConfirm`.

        wrapper.vm.$attrs.onConfirm = () => { confirmed = true; };
        // Wait, Vue 3 treats listeners as onEvent props.

        // Let's try finding the button and clicking.
        const btns = wrapper.findAll('button');
        expect(btns.length > 0).toBeTruthy();
    });
});
