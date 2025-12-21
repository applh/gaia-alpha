<?php
// templates/layout/header.php
// Expected variables: $title, $desc, $keywords, $page (optional), $globalSettings (optional)
$page = $page ?? null;
$globalSettings = $globalSettings ?? []; // Default to empty if not passed

// SEO Title Logic
$siteTitle = $globalSettings['site_title'] ?? null;
$siteLogo = $globalSettings['site_logo'] ?? null;
$pageTitle = $title ?? ($page['title'] ?? null);

if ($siteTitle) {
    if ($pageTitle && $pageTitle !== $siteTitle) {
        $displayTitle = "$pageTitle | $siteTitle";
    } else {
        $displayTitle = $siteTitle;
    }
} else {
    $displayTitle = $pageTitle ?? 'Gaia Alpha';
}

$desc = $desc ?? ($page['meta_description'] ?? ($globalSettings['site_description'] ?? 'Gaia Alpha - Enterprise
Solution'));
$keywords = $keywords ?? ($page['meta_keywords'] ?? ($globalSettings['site_keywords'] ?? ''));
$favicon = $globalSettings['site_favicon'] ?? null;

// Fetch Menus for Navigation
// Fetch Menus for Navigation
$mainMenu = \GaiaAlpha\Model\Menu::findByLocation('main');
$navItems = $mainMenu ? json_decode($mainMenu['items'], true) : [];
$appSlug = \GaiaAlpha\Model\Page::getAppDashboard();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($globalSettings['site_language'] ?? 'en') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($displayTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($desc) ?>">
    <?php if (isset($page['canonical_url'])): ?>
        <link rel="canonical" href="<?= htmlspecialchars($page['canonical_url']) ?>">
    <?php endif; ?>
    <?php if ($favicon): ?>
        <link rel="icon" href="<?= htmlspecialchars($favicon) ?>">
    <?php endif; ?>
    <?php if ($keywords): ?>
        <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
    <?php endif; ?>
    <?php if (isset($page['image'])): ?>
        <meta property="og:image" content="<?= htmlspecialchars($page['image']) ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="<?= \GaiaAlpha\Asset::url('/css/site.css') ?>">
    <style>
        /* Shared Header/Footer Styles (Ideally move to site.css) */

        /* Navigation */
        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(9, 9, 11, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: var(--space-md) var(--space-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .site-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
            letter-spacing: -0.02em;
        }

        .site-nav {
            display: flex;
            gap: var(--space-lg);
            align-items: center;
        }

        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
            font-size: 0.95rem;
        }

        .nav-link:hover {
            color: var(--text-primary);
        }

        .nav-cta {
            background: var(--accent-color);
            color: white;
            padding: 8px 20px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .nav-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        /* Mobile */
        @media (max-width: 768px) {
            .site-header {
                flex-direction: column;
                gap: 15px;
            }

            .site-nav {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        /* Body & Layout */
        body {
            /* Ensure footer stays at bottom */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex: 1;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <header class="site-header">
        <a href="/" class="site-brand">
            <?php if ($siteLogo): ?>
                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteTitle ?? 'Gaia Alpha') ?>"
                    style="height: 32px; vertical-align: middle;">
            <?php else: ?>
                <?= htmlspecialchars($siteTitle ?? 'Gaia Alpha') ?>
            <?php endif; ?>
        </a>
        <nav class="site-nav">
            <?php foreach ($navItems as $item): ?>
                <?php if (($item['text'] ?? '') === 'App Dashboard')
                    continue; ?>
                <a href="<?= htmlspecialchars($item['href']) ?>" class="nav-link"><?= htmlspecialchars($item['text']) ?></a>
            <?php endforeach; ?>
            <?php if ($appSlug): ?>
                <a href="/<?= htmlspecialchars($appSlug) ?>" class="nav-cta">Launch App</a>
            <?php endif; ?>
        </nav>
    </header>

    <!-- Main Content Wrapper -->
    <main>