<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaia Alpha</title>
    <script type="importmap">
        {
            "imports": {
                "vue": "<?= \GaiaAlpha\Asset::url('/js/vendor/vue.esm-browser.js') ?>",
                "marked": "<?= \GaiaAlpha\Asset::url('/js/vendor/marked.esm.js') ?>",
                "@/": "/min/js/",
                "components/": "/min/js/components/",
                "ui/": "/min/js/components/ui/",
                "composables/": "/min/js/composables/",
                "builders/": "/min/js/components/builders/",
                "plugins/": "/min/js/plugins/",
                "store": "/min/js/store.js"
            }
        }
    </script>
    <script>
        window.siteConfig = <?= json_encode($globalSettings ?? []) ?>;
    </script>
    <link rel="stylesheet" href="<?= \GaiaAlpha\Asset::url('/css/site.css') ?>">
    <?php if (!empty($globalSettings['ui_styles'])): ?>
        <?php foreach ($globalSettings['ui_styles'] as $stylePath): ?>
            <link rel="stylesheet" href="<?= \GaiaAlpha\Asset::url('/' . $stylePath) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <script type="module" src="<?= \GaiaAlpha\Asset::url('/js/site.js?v=2') ?>"></script>
    <script src="<?= \GaiaAlpha\Asset::url('/js/vendor/lucide.min.js') ?>"></script>
    <link rel="stylesheet" href="<?= \GaiaAlpha\Asset::url('/css/fonts.css') ?>">
</head>

<body>
    <div id="app"></div>
</body>

</html>