<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaia Alpha Test Runner</title>
    <script type="importmap">
    {
        "imports": {
            "vue": "/resources/js/vendor/vue.esm-browser.js",
            "api": "/resources/js/utils/Api.js",
            "store": "/resources/js/store.js",
            "ui/": "/resources/js/components/ui/",
            "components/": "/resources/js/components/",
            "composables/": "/resources/js/composables/",
            "@/": "/resources/js/",
            "builders/": "/resources/js/components/builders/",
            "bitter/": "/resources/js/utils/bitter/",
            "plugins/": "/plugins/",
            "plugins/Todo/": "/plugins/Todo/resources/js/",
            "/min/js/plugins/Todo/": "/plugins/Todo/resources/js/",
            "plugins/ComponentBuilder/": "/plugins/ComponentBuilder/resources/js/",
            "test-utils": "/tests/js/framework/test-utils.js"
        }
    }
    </script>
    <style>
        :root {
            --bg-color: #f8f9fa;
            --text-color: #212529;
            --success-color: #198754;
            --error-color: #dc3545;
            --border-color: #dee2e6;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: var(--bg-color);
            color: var(--text-color);
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 300px;
            background: white;
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            background: #fff;
            position: sticky;
            top: 0;
        }

        .main {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .test-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .test-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .test-item:hover {
            background-color: #e9ecef;
        }

        .test-item.active {
            background-color: #e7f1ff;
            color: #0d6efd;
            font-weight: 500;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ccc;
        }

        .status-dot.passed {
            background: var(--success-color);
        }

        .status-dot.failed {
            background: var(--error-color);
        }

        .btn {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
        }

        .btn:hover {
            background: #0b5ed7;
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .report-suite {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .report-header {
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
        }

        .report-test {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f1f1;
            display: flex;
            align-items: center;
        }

        .report-test:last-child {
            border-bottom: none;
        }

        .test-name {
            flex: 1;
            margin-left: 0.5rem;
        }

        .error-msg {
            display: block;
            color: var(--error-color);
            font-family: monospace;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            margin-left: 1.5rem;
        }
    </style>
</head>

<body>
    <div id="app" style="display: contents;">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Gaia Test Runner</h3>
                <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                    <button class="btn" @click="runAll" :disabled="running">{{ running ? 'Running...' : 'Run All'
                        }}</button>
                </div>
            </div>
            <ul class="test-list">
                <li v-for="test in tests" :key="test.path" class="test-item"
                    :class="{ active: currentTest?.path === test.path }" @click="runSingle(test)">
                    <span>{{ test.name }}</span>
                    <span class="status-dot" :class="test.status"></span>
                </li>
            </ul>
        </div>
        <div class="main">
            <div v-if="!currentTest && !logs.length" style="color: #666; text-align: center; margin-top: 20vh;">
                <h2>Select a test to run</h2>
                <p>Or click "Run All" to execute the full suite.</p>
            </div>

            <div v-for="(log, index) in logs" :key="index" class="report-suite">
                <div class="report-header">
                    <span>{{ log.name }}</span>
                    <span>{{ log.passed }}/{{ log.total }}</span>
                </div>
                <div v-for="t in log.tests" :key="t.name" class="report-test">
                    <span class="status-dot" :class="t.status"></span>
                    <div class="test-name">
                        {{ t.name }}
                        <span v-if="t.error" class="error-msg">{{ t.error }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        import { createApp, ref, reactive } from 'vue';
        import { runSuites, clearSuites } from 'test-utils';

        const app = createApp({
            setup() {
                const tests = ref([]);
                const running = ref(false);
                const currentTest = ref(null);
                const logs = reactive([]);

                // API: Fetch Tests
                const fetchTests = async () => {
                    const res = await fetch('/api/tests');
                    tests.value = (await res.json()).map(t => ({ ...t, status: '' }));
                };

                // API: Report to Server
                const reportToServer = async (type, data) => {
                    try {
                        await fetch('/api/report', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ type, data })
                        });
                    } catch (e) { console.error("Report failed", e); }
                };

                // Runner Logic
                const runTestFile = async (test) => {
                    currentTest.value = test;
                    test.status = 'running';

                    // Clear previous suites from memory
                    clearSuites();

                    try {
                        // Dynamic Import
                        await import('/tests/js/' + test.path + '?t=' + Date.now());

                        // Execute
                        const results = await runSuites();

                        // Update UI
                        const hasFailure = results.some(s => s.failed > 0);
                        test.status = hasFailure ? 'failed' : 'passed';

                        // Add to logs
                        results.forEach(suite => logs.unshift(suite));

                        // Report
                        for (const s of results) {
                            for (const t of s.tests) {
                                await reportToServer('progression', {
                                    suite: s.name,
                                    test: t.name,
                                    status: t.status,
                                    error: t.error
                                });
                            }
                        }

                        return !hasFailure;
                    } catch (e) {
                        console.error(e);
                        test.status = 'failed';
                        logs.unshift({
                            name: test.name,
                            tests: [{ name: 'File Load Error', status: 'failed', error: e.message }],
                            passed: 0,
                            total: 1
                        });
                        return false;
                    }
                };

                const runAll = async () => {
                    if (running.value) return;
                    running.value = true;
                    logs.length = 0; // Clear logs

                    let passed = 0;
                    let failed = 0;

                    await reportToServer('start', { total: tests.value.length });

                    for (const test of tests.value) {
                        const success = await runTestFile(test);
                        if (success) passed++; else failed++;
                    }

                    await reportToServer('result', { passedCount: passed, failedCount: failed });
                    running.value = false;
                };

                const runSingle = async (test) => {
                    if (running.value) return;
                    logs.length = 0;
                    await runTestFile(test);
                };

                // Initialize
                fetchTests().then(() => {
                    // Auto-run if param exists
                    const params = new URLSearchParams(window.location.search);
                    if (params.get('mode') === 'auto') {
                        runAll();
                    }
                });

                return { tests, running, currentTest, logs, runAll, runSingle };
            }
        });

        window.addEventListener('load', () => {
            app.mount('#app');
        });
    </script>
</body>

</html>