// Micro-testing framework (Jasmine/Jest style)

export let suites = [];
let currentSuite = null;

export function describe(name, fn) {
    currentSuite = {
        name,
        tests: [],
        beforeEach: [],
        afterEach: []
    };
    suites.push(currentSuite);
    try {
        fn();
    } catch (e) {
        console.error(`Error in suite definition "${name}":`, e);
    }
    currentSuite = null;
}

export function it(name, fn) {
    if (!currentSuite) {
        throw new Error("it() must be called inside describe()");
    }
    currentSuite.tests.push({ name, fn });
}

export function beforeEach(fn) {
    if (currentSuite) currentSuite.beforeEach.push(fn);
}

export function afterEach(fn) {
    if (currentSuite) currentSuite.afterEach.push(fn);
}

export function clearSuites() {
    suites = [];
}

export async function runSuites() {
    const results = [];

    for (const suite of suites) {
        const suiteResult = {
            name: suite.name,
            tests: [],
            passed: 0,
            failed: 0,
            total: suite.tests.length
        };

        for (const test of suite.tests) {
            try {
                // Run beforeEach
                for (const hook of suite.beforeEach) await hook();

                await test.fn();

                // Run afterEach
                for (const hook of suite.afterEach) await hook();

                suiteResult.tests.push({ name: test.name, status: 'passed' });
                suiteResult.passed++;
            } catch (e) {
                console.error(`Test failed: ${test.name}`, e);
                suiteResult.tests.push({ name: test.name, status: 'failed', error: e.message });
                suiteResult.failed++;
            }
        }
        results.push(suiteResult);
    }
    return results;
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
        },
        toBeNull() {
            if (actual !== null) throw new Error(`Expected ${actual} to be null`);
        }
    };
}

// Vue Component Utils
import { createApp, h, nextTick } from '/resources/js/vendor/vue.esm-browser.js';

let appInstance = null;
let container = null;

export function mount(Component, options = {}) {
    // Cleanup previous mount
    if (appInstance) {
        appInstance.unmount();
        if (container && container.parentNode) container.parentNode.removeChild(container);
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

    // Install plugins if needed
    if (options.global && options.global.plugins) {
        options.global.plugins.forEach(p => appInstance.use(p));
    }

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
            await nextTick();
        }
    };
}

export async function flushPromises() {
    return new Promise(resolve => setTimeout(resolve, 0));
}
