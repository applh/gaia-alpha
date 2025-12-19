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

<body class="template-landing" style="--hero-image: url('<?php echo $page['image'] ?? '/assets/logo.svg'; ?>')">
    <header>
        <div class="container">
            <img src="/assets/logo.svg" alt="SportFlow">
            <nav>
                <a href="/">Home</a>
                <a href="/about-us">About</a>
                <a href="/services">Technology</a>
                <a href="/contact">Get Started</a>
            </nav>
        </div>
    </header>
    <main>
        <section class="hero">
            <div class="hero-content container">
                <h1><?php echo $page['title']; ?></h1>
                <p><?php echo $page['meta_description'] ?? 'Redefining athletic performance through data-driven innovation.'; ?>
                </p>
                <a href="/services" class="cta-button">Explore Tech</a>
            </div>
        </section>
        <div class="content-section container">
            <div class="glass-card">
                <?php echo $page['content']; ?>
            </div>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> SportFlow. Elite Performance Analytics.</p>
        </div>
    </footer>
</body>

</html>