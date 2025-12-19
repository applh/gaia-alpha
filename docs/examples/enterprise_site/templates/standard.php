<?php
// Template: Standard
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
    <meta property="og:type" content="article">

    <link rel="canonical" href="/<?php echo $page['slug']; ?>">
    <link rel="stylesheet" href="/assets/styles.css">
</head>

<body class="template-standard">
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
        <?php if (!empty($page['image'])): ?>
            <section class="hero" style="--hero-image: url('<?php echo $page['image']; ?>')">
                <div class="hero-content container">
                    <h1><?php echo ucwords($page['title']); ?></h1>
                </div>
            </section>
        <?php endif; ?>
        <div class="content-section container">
            <div class="grid grid-md-12">
                <article class="glass-card col-12 col-md-8">
                    <div class="markdown-body">
                        <?php echo $page['content']; ?>
                    </div>
                </article>
                <aside class="col-12 col-md-4">
                    <div class="glass-card" style="border-left: 4px solid var(--primary);">
                        <h3>Performance Hub</h3>
                        <p>Unlock elite insights with our specialized sports analytics platform.</p>
                        <a href="/contact" class="cta-button">Consult an Expert</a>
                    </div>
                </aside>
            </div>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> SportFlow. Driven by Data.</p>
        </div>
    </footer>
</body>

</html>