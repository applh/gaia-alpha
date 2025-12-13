<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaia Alpha</title>
    <script type="importmap">
        {
            "imports": {
                "vue": "<?= \GaiaAlpha\Asset::url('/js/vendor/vue.esm-browser.js') ?>"
            }
        }
    </script>
    <link rel="stylesheet" href="<?= \GaiaAlpha\Asset::url('/css/site.css') ?>">
    <script type="module" src="<?= \GaiaAlpha\Asset::url('/js/site.js?v=2') ?>"></script>
    <script src="<?= \GaiaAlpha\Asset::url('/js/vendor/lucide.min.js') ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div id="app"></div>
</body>

</html>