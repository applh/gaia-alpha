// Micro-testing framework (Jasmine/Jest style)

export const suites = [];
let currentSuite = null;

export function describe(name, fn) {
    currentSuite = {
        name,
        tests: [],
        beforeEach: [],
        afterEach: []
    };
    suites.push(currentSuite);
    fn();
    currentSuite = null;
}

export function it(name, fn) {
    if (!currentSuite) {
        throw new Error("it() must be called inside describe()");
    }
    currentSuite.tests.push({ name, fn });
}

export function expect(actual) {
    return {
        toBe(expected) {
            if (actual !== expected) {
                throw new Error(`Expected ${actual} to be ${expected}`);
            }
        },
        toEqual(expected) {
            const actualJson = JSON.stringify(actual);
            const expectedJson = JSON.stringify(expected);
            if (actualJson !== expectedJson) {
                throw new Error(`Expected ${actualJson} to equal ${expectedJson}`);
            }
        },
        toContain(expected) {
            if (typeof actual === 'string') {
                if (!actual.includes(expected)) {
                    throw new Error(`Expected string "${actual}" to contain "${expected}"`);
                }
            } else if (Array.isArray(actual)) {
                if (!actual.includes(expected)) {
                    throw new Error(`Expected array to contain ${expected}`);
                }
            } else {
                throw new Error(`toContain only works on strings and arrays`);
            }
        },
        toBeTruthy() {
            if (!actual) throw new Error(`Expected ${actual} to be truthy`);
        },
        toBeFalsy() {
            if (actual) throw new Error(`Expected ${actual} to be falsy`);
        }
    };
}

// Vue Component Utils
import { createApp, h } from '/resources/js/vendor/vue.esm-browser.js';

let appInstance = null;
let container = null;

export function mount(Component, options = {}) {
    // Cleanup previous mount
    if (appInstance) {
        appInstance.unmount();
        document.body.removeChild(container);
    }

    container = document.createElement('div');
    document.body.appendChild(container);

    // Create a wrapper app that renders the component with props and slots
    const Wrapper = {
        render() {
            // Convert simple string slots to functions for Vue 3
            const slots = {};
            if (options.slots) {
                for (const key in options.slots) {
                    slots[key] = () => options.slots[key];
                }
            }
            return h(Component, options.props, slots);
        }
    };

    appInstance = createApp(Wrapper);

    // Stub plugins/mixins if needed...

    const vm = appInstance.mount(container);

    return {
        vm,
        element: container.firstElementChild,
        text() {
            return container.textContent;
        },
        html() {
            return container.innerHTML;
        },
        find(selector) {
            return container.querySelector(selector);
        },
        findAll(selector) {
            return container.querySelectorAll(selector);
        },
        async trigger(eventName) {
            const el = container.firstElementChild;
            const event = new Event(eventName);
            el.dispatchEvent(event);
            await flushPromises();
        }
    };
}

export async function flushPromises() {
    return new Promise(resolve => setTimeout(resolve, 0));
}
