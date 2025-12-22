<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form | Gaia Alpha</title>
    <link rel="stylesheet" href="/min/css/site.css">
    <style>
        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .public-form-container {
            width: 100%;
            max-width: 600px;
            padding: var(--space-md);
        }

        .form-card {
            background: var(--card-bg);
            padding: var(--space-lg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div id="app" class="public-form-container">
        <!-- We pass the slug to Javascript via data attribute or global variable -->
        <public-form-viewer slug="<?= htmlspecialchars($slug) ?>"></public-form-viewer>
    </div>

    <!-- Vue 3 -->
    <script type="importmap">
    {
        "imports": {
            "vue": "/min/js/vendor/vue.esm-browser.js"
        }
    }
    </script>

    <script type="module">
        import { createApp } from 'vue';
        import PublicFormViewer from '/min/js/components/public/PublicFormViewer.js';

        const app = createApp({
            components: { PublicFormViewer }
        });
        app.mount('#app');
    </script>
</body>

</html>