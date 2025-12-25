import ToastContainer from 'components/ui/ToastContainer.js';
import { describe, it, expect, mount, flushPromises } from '../framework/test-utils.js';
import { store } from 'store';

describe('ToastContainer', () => {
    it('renders notifications from store', async () => {
        // Reset store
        store.state.notifications = [];

        const wrapper = mount(ToastContainer);

        // Add a notification
        store.addNotification('Test Message', 'success');
        await flushPromises();

        expect(wrapper.text()).toContain('Test Message');
        expect(wrapper.element.querySelector('.toast.success')).toBeTruthy();
    });

    it('removes notification on click', async () => {
        store.state.notifications = [];
        store.addNotification('To Be Removed', 'info');
        await flushPromises();

        const wrapper = mount(ToastContainer);
        expect(wrapper.text()).toContain('To Be Removed');

        const closeBtn = wrapper.findAll('.toast-close')[0];
        closeBtn.click();
        await flushPromises();

        // Note: transitions might delay removal from DOM in Vue, but data should be gone.
        // But our mock store removes it instantly.
        // Wait, store.removeNotification modifies state. Vue reacts.
        // Transition might keep it in DOM for a bit.
        // Check store state primarily?
        expect(store.state.notifications.length).toBe(0);
    });
});
