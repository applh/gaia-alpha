import TodoList from 'plugins/Todo/TodoList.js';
// We use the same path as site.js uses, knowing our import map maps it to the file.
// Or we can import directly if we prefer?
// "plugins/" -> "/plugins/"
// "/min/js/plugins/Todo/" -> "/plugins/Todo/resources/js/"
// Let's use the mapped path to verify mapping works too.

import { describe, it, expect, mount, flushPromises } from '../../framework/test-utils.js';
import { store } from 'store';

describe('Todo List Plugin', () => {
    it('renders main view', async () => {
        // Mock fetch for todos
        const originalFetch = window.fetch;
        window.fetch = async (url) => {
            if (url.includes('todos')) {
                return {
                    ok: true,
                    json: async () => ([
                        { id: 1, title: 'Test Todo', completed: 0, labels: 'Work' }
                    ])
                };
            }
            if (url.includes('settings')) {
                return {
                    ok: true,
                    json: async () => ({ settings: {} })
                };
            }
            // Add mocks for other endpoints if needed, or default error with json
            return {
                ok: false,
                status: 404,
                json: async () => ({ error: 'Not Found' })
            };
        };

        const wrapper = mount(TodoList);
        await flushPromises();

        expect(wrapper.text()).toContain('Projects');
        expect(wrapper.text()).toContain('Filter by label');

        // Check if todo loaded
        // TreeView might take a moment or render slots.
        await flushPromises();
        expect(wrapper.text()).toContain('Test Todo');

        window.fetch = originalFetch;
    });
});
