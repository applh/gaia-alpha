<?php
// Template: Landing
?>
<!DOCTYPE html>
<html lang="<?php echo $page['lang'] ?? 'en'; ?>">

<head>
    <meta charset="UTF-8">
    <title><?php echo $page['title']; ?></title>
    <meta name="description" content="<?php echo $page['meta_description'] ?? ''; ?>">
    <meta name="keywords" content="<?php echo $page['meta_keywords'] ?? ''; ?>">

    <!-- Open Graph -->
    <meta property="og:locale" content="<?php echo $page['locale'] ?? 'en_US'; ?>">
    <meta property="og:title" content="<?php echo $page['title']; ?>">
    <meta property="og:description" content="<?php echo $page['meta_description'] ?? ''; ?>">
    <meta property="og:image" content="<?php echo $page['image'] ?? '/assets/logo.svg'; ?>">
    <meta property="og:url" content="/<?php echo $page['slug']; ?>">
    <meta property="og:type" content="website">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $page['title']; ?>">
    <meta name="twitter:description" content="<?php echo $page['meta_description'] ?? ''; ?>">
    <meta name="twitter:image" content="<?php echo $page['image'] ?? '/assets/logo.svg'; ?>">

    <link rel="canonical" href="/<?php echo $page['slug']; ?>">
    <link rel="stylesheet" href="/assets/styles.css">
</head>

<body class="template-landing">
    <header>
        <img src="/assets/logo.svg" alt="Acme Corp">
        <nav>
            <a href="/">Home</a>
            <a href="/about-us">About</a>
            <a href="/services">Services</a>
            <a href="/contact">Contact</a>
        </nav>
    </header>
    <main>
        <?php echo $page['content']; ?>
    </main>
    <footer>
        &copy; <?php echo date('Y'); ?> Acme Corp.
    </footer>
</body>

</html>