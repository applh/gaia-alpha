
// Mock window and localStorage
global.window = {
    location: {
        hash: '',
        search: ''
    },
    addEventListener: (event, callback) => {
        if (event === 'hashchange') {
            global.window.onhashchange = callback;
        }
    }
};

global.localStorage = {
    getItem: () => null,
    setItem: () => { }
};

global.document = {
    body: {
        classList: {
            add: () => { },
            remove: () => { }
        }
    }
};

// Import store (using dynamic import to handle ESM)
async function runTests() {
    console.log("Starting Routing Verification...");

    // Test 1: Initialization
    window.location.hash = '#initial';
    const { store } = await import('../resources/js/store.js');

    if (store.state.currentView === 'initial') {
        console.log("PASS: Initialize from hash");
    } else {
        console.error("FAIL: Initialize from hash. Expected 'initial', got", store.state.currentView);
    }

    // Test 2: setView updates hash
    store.setView('settings');
    if (window.location.hash === 'settings') { // setView sets it to 'settings', browser usually prepends # but our mock is simple
        console.log("PASS: setView updates hash (mock check)");
    } else if (window.location.hash === '#settings' || window.location.hash === 'settings') {
        console.log("PASS: setView updates hash");
    } else {
        console.error("FAIL: setView updates hash. Expected 'settings', got", window.location.hash);
    }

    // Test 3: Hash change updates view
    window.location.hash = '#users';
    if (window.onhashchange) window.onhashchange();

    if (store.state.currentView === 'users') {
        console.log("PASS: Hash change updates view");
    } else {
        console.error("FAIL: Hash change updates view. Expected 'users', got", store.state.currentView);
    }
}

runTests();
