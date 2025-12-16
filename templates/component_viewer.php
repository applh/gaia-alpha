<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Component Viewer - <?= htmlspecialchars($name) ?></title>
    <script type="importmap">
        {
            "imports": {
                "vue": "<?= \GaiaAlpha\Asset::url('/js/vendor/vue.esm-browser.js') ?>"
            }
        }
    </script>
    <link rel="stylesheet" href="<?= \GaiaAlpha\Asset::url('/css/site.css') ?>">
    <link rel="stylesheet" href="<?= \GaiaAlpha\Asset::url('/css/fonts.css') ?>">
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #18181b;
            color: #fff;
        }

        #app {
            margin: 0 auto;
            max-width: 1200px;
        }
    </style>
</head>

<body>
    <div id="app">
        <div v-if="loading">Loading component...</div>
        <component v-else :is="component" />
        <div v-if="error" style="color:red; margin-top:20px;">{{ error }}</div>
    </div>

    <script type="module">
        import { createApp, defineAsyncComponent, ref, onMounted, shallowRef } from 'vue';

        const app = createApp({
            setup() {
                const component = shallowRef(null);
                const loading = ref(true);
                const error = ref(null);

                onMounted(async () => {
                    try {
                        // Dynamic import of the component
                        // The backend must ensure this file exists
                        const module = await import('<?= \GaiaAlpha\Asset::url("/js/components/custom/{$viewName}.js") ?>?t=' + Date.now());
                        component.value = module.default || module;
                    } catch (e) {
                        console.error(e);
                        error.value = "Failed to load component: " + e.message;
                    } finally {
                        loading.value = false;
                    }
                });

                return { component, loading, error };
            }
        });

        app.mount('#app');
    </script>
</body>

</html>