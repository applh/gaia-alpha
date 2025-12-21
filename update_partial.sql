UPDATE cms_partials SET content = '<?php
// templates/layout/header.php
// Expected variables: $title, $desc, $keywords, $page (optional), $globalSettings (optional)
$page = $page ?? null;
$globalSettings = $globalSettings ?? []; // Default to empty if not passed

// SEO Title Logic
$siteTitle = $globalSettings[''site_title''] ?? null;
$siteLogo = $globalSettings[''site_logo''] ?? null;
$pageTitle = $title ?? ($page[''title''] ?? null);

if ($siteTitle) {
    if ($pageTitle && $pageTitle !== $siteTitle) {
        $displayTitle = "$pageTitle | $siteTitle";
    } else {
        $displayTitle = $siteTitle;
    }
} else {
    $displayTitle = $pageTitle ?? ''Gaia Alpha'';
}

$desc = $desc ?? ($page[''meta_description''] ?? ($globalSettings[''site_description''] ?? ''Gaia Alpha - Enterprise Solution''));
$keywords = $keywords ?? ($page[''meta_keywords''] ?? ($globalSettings[''site_keywords''] ?? ''''));
$favicon = $globalSettings[''site_favicon''] ?? null;

// Fetch Menus for Navigation
$mainMenu = \GaiaAlpha\Model\Menu::findByLocation(''main'');
$navItems = $mainMenu ? json_decode($mainMenu[''items''], true) : [];
$appSlug = \GaiaAlpha\Model\Page::getAppDashboard();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($globalSettings[''site_language''] ?? ''en'') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($displayTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($desc) ?>">
    <?php if (isset($page[''canonical_url''])): ?>
        <link rel="canonical" href="<?= htmlspecialchars($page[''canonical_url'']) ?>">
    <?php endif; ?>
    <?php if (isset($schemaJson)): ?>
        <script type="application/ld+json">
            <?= $schemaJson ?>
        </script>
    <?php endif; ?>
    <?php if ($favicon): ?>
        <link rel="icon" href="<?= htmlspecialchars($favicon) ?>">
    <?php endif; ?>
    <?php if ($keywords): ?>
        <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
    <?php endif; ?>
    <?php if (isset($page[''image''])): ?>
        <meta property="og:image" content="<?= htmlspecialchars($page[''image'']) ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="<?= \GaiaAlpha\Asset::url(''/css/site.css'') ?>">
</head>

<body>
    <!-- Navigation simplified for partial -->
    <header class="site-header">
        <a href="/" class="site-brand">
            <?php if ($siteLogo): ?>
                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteTitle ?? ''Gaia Alpha'') ?>"
                    style="height: 32px; vertical-align: middle;">
            <?php else: ?>
                <?= htmlspecialchars($siteTitle ?? ''Gaia Alpha'') ?>
            <?php endif; ?>
        </a>
        <nav class="site-nav">
            <?php foreach ($navItems as $item): ?>
                <?php if (($item[''text''] ?? '''') === ''App Dashboard'')
                    continue; ?>
                <a href="<?= htmlspecialchars($item[''href'']) ?>" class="nav-link"><?= htmlspecialchars($item[''text'']) ?></a>
            <?php endforeach; ?>
            <?php if ($appSlug): ?>
                <a href="/<?= htmlspecialchars($appSlug) ?>" class="nav-cta">Launch App</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>' WHERE name = 'site_header';
