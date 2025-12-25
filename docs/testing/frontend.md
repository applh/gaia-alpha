# Frontend Testing Guide

Our JavaScript testing framework is a browser-native implementation using ES Modules. It requires no build step (Webpack/Vite).

## File Structure
- Tests are located in `tests/js/`.
- Convention: `ComponentNameTest.js`.
- You **MUST** register your test file in `tests/js/tests.js` to be included in the runner.

## Writing a Test

```javascript
// tests/js/samples/MyComponentTest.js
import MyComponent from '../../../resources/js/components/MyComponent.js';
import { describe, it, expect, mount } from '../framework/test-utils.js';

describe('My Component', () => {
    it('renders correctly', () => {
        const wrapper = mount(MyComponent, {
            props: { title: 'Hello' }
        });
        
        expect(wrapper.text()).toContain('Hello');
    });
});
```

## Registering the Test
Add your import to `tests/js/tests.js`:
```javascript
import './samples/MyComponentTest.js';
```

## Available Utilities (`test-utils.js`)
- `describe(name, fn)`: Group tests.
- `it(name, fn)`: Define a test case.
- `expect(value)`: Assertion builder.
    - `.toBe(expected)`
    - `.toEqual(expected)` (deep comparison)
    - `.toContain(expected)` (string or array)
    - `.toBeTruthy()`
    - `.toBeFalsy()`
- `mount(Component, options)`: Mounts a Vue component. Returns a wrapper with:
    - `.element`: Raw DOM element.
    - `.find(selector)`: `querySelector`.
    - `.text()`: `textContent`.
    - `.html()`: `innerHTML`.

## Running Tests

To run frontend tests, you need a server that has access to both the `tests/` directory and the `resources/` directory. The default application server (rooted in `www/`) cannot access these.

1. **Start the Test Server**:
   ```bash
   php scripts/test_server.php
   ```

2. **Open the Runner**:
   Go to **[http://localhost:8001/tests/js/index.html](http://localhost:8001/tests/js/index.html)**
