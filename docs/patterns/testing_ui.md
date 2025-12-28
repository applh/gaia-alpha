# UI Testing Pattern

This document outlines the patterns for testing Vue 3 components in Gaia Alpha using the custom UI Test Runner.

## Anatomy of a UI Test

UI tests are written in JavaScript directly (no build step) and use a Jest-like syntax provided by `tests/js/framework/test-utils.js`.

```javascript
import MyComponent from 'components/MyComponent.js';
import { describe, it, expect, mount, flushPromises } from '../framework/test-utils.js';

describe('MyComponent', () => {
    it('renders correctly', async () => {
        const wrapper = mount(MyComponent, {
            props: { title: 'Hello' }
        });
        
        await flushPromises(); // Wait for async rendering
        
        expect(wrapper.text()).toContain('Hello');
    });
});
```

## Key Utilities

### `mount(Component, options)`
Mounts a Vue component and returns a wrapper with helper methods:
-   `wrapper.element`: The raw DOM element.
-   `wrapper.find(selector)`: Returns the first matching DOM element (or null).
-   `wrapper.findAll(selector)`: Returns all matching DOM elements.
-   `wrapper.text()`: Returns the text content of the component.
-   `wrapper.html()`: Returns the HTML content.
-   `wrapper.trigger(event)`: Triggers a DOM event (async).

### `flushPromises()`
Crucial for testing async components or waiting for API mocks to resolve. Always `await flushPromises()` after mounting components that fetch data or use `defineAsyncComponent`.

## Mocking API Calls

Since there is no backend during UI tests, you **must** mock `window.fetch`.

```javascript
it('fetches data', async () => {
    const originalFetch = window.fetch;
    window.fetch = async (url) => {
        if (url.includes('/api/data')) {
            return {
                ok: true,
                json: async () => ([ { id: 1, name: 'Test Item' } ])
            };
        }
        return { ok: false, status: 404 };
    };

    const wrapper = mount(DataList);
    await flushPromises();

    expect(wrapper.text()).toContain('Test Item');

    // Restore fetch
    window.fetch = originalFetch;
});
```

## Testing Async Components

Components that import others using `defineAsyncComponent` (like `PasswordInput`) require extra care. You may need a small delay or multiple `flushPromises`.

```javascript
import Login from 'components/admin/Login.js';

it('renders async inputs', async () => {
    const wrapper = mount(Login);
    
    // Tiny delay to allow async component loader to start
    await new Promise(r => setTimeout(r, 100));
    await flushPromises();

    // Now query the DOM
    const input = wrapper.find('input[type="password"]');
    expect(input).toBeTruthy();
});
```

## Best Practices

1.  **Cleanup**: The `mount` function automatically cleans up the previous test's DOM.
2.  **Isolation**: Mock all external dependencies (API, Store methods) to ensure tests are isolated.
3.  **Selectors**: Use `data-testid` attributes or specific class names for robust selectors.
4.  **Assertions**: Use `expect(wrapper.text()).toContain(...)` for content checks and `expect(el).toBeTruthy()` for existence checks.
