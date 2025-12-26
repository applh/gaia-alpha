<?php
$page = $page ?? null;
use GaiaAlpha\Helper\Part;
// Variables for layout/header.php
// Use Page Title -> Global Title -> Hardcoded Fallback
$title = $page['title'] ?? $globalSettings['site_title'] ?? 'Gaia Alpha - Enterprise Solution';
$desc = $page['meta_description'] ?? $globalSettings['site_description'] ?? 'The unified open-source operating system for your digital life. Scalable, Secure, Simple.';
$keywords = $page['meta_keywords'] ?? $globalSettings['site_keywords'] ?? 'enterprise, software, open source, gaia alpha';

// Fetch latest pages/news
$latestPages = \GaiaAlpha\Model\Page::getLatestPublic(3);

?>
<?php Part::in("site_header") ?>
<style>
    /* Enterprise Home Specific Overrides */
    :root {
        --hero-height: 80vh;
    }

    /* Hero Section */
    .hero-section {
        min-height: var(--hero-height);
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 120px 20px 60px;
        position: relative;
        overflow: hidden;
    }

    .hero-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        background:
            radial-gradient(circle at 20% 40%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
            radial-gradient(circle at 80% 60%, rgba(16, 185, 129, 0.1) 0%, transparent 40%);
    }

    .hero-content {
        max-width: 900px;
        z-index: 1;
    }

    .hero-title {
        font-size: 4rem;
        line-height: 1.1;
        margin-bottom: var(--space-lg);
        background: linear-gradient(135deg, #fff 0%, #a5a5a5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 700;
        letter-spacing: -0.03em;
    }

    .hero-subtitle {
        font-size: 1.5rem;
        color: var(--text-secondary);
        margin-bottom: var(--space-xl);
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }

    .hero-actions {
        display: flex;
        gap: var(--space-md);
        justify-content: center;
    }

    .btn-lg {
        padding: 12px 32px;
        font-size: 1.1rem;
    }

    /* Features Section */
    .features-section {
        padding: 100px 20px;
        background: rgba(255, 255, 255, 0.01);
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
    }

    .section-header {
        text-align: center;
        margin-bottom: 80px;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: var(--space-md);
    }

    .section-desc {
        font-size: 1.2rem;
        color: var(--text-secondary);
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 40px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .feature-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        padding: 40px;
        border-radius: var(--radius-lg);
        transition: transform 0.3s;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent-color);
    }

    .feature-icon {
        font-size: 2rem;
        color: var(--accent-color);
        margin-bottom: var(--space-md);
        display: inline-block;
    }

    .feature-title {
        font-size: 1.5rem;
        margin-bottom: var(--space-sm);
    }

    .feature-text {
        color: var(--text-secondary);
        line-height: 1.6;
    }

    /* Trust Section */
    .trust-section {
        padding: 80px 20px;
        text-align: center;
    }

    .logo-grid {
        display: flex;
        justify-content: center;
        gap: 60px;
        flex-wrap: wrap;
        opacity: 0.5;
        margin-top: 40px;
        filter: grayscale(100%);
    }

    /* Stats Section */
    .stats-section {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        max-width: 1000px;
        margin: 0 auto;
        text-align: center;
        padding: 60px 0;
    }

    .stat-item h3 {
        font-size: 3rem;
        color: var(--accent-color);
        margin-bottom: 5px;
    }

    .stat-item p {
        color: var(--text-secondary);
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }

        .stats-section {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<!-- Hero -->
<section class="hero-section">
    <div class="hero-bg"></div>
    <div class="hero-content">
        <?php if ($page && !empty($page['content'])): ?>
            <?php
            if (isset($page['content_format']) && $page['content_format'] === 'markdown') {
                $parsedown = new \GaiaAlpha\Helper\Parsedown();
                echo $parsedown->text($page['content']);
            } else {
                echo $page['content'];
            }
            ?>
        <?php else: ?>
            <h1 class="hero-title">The Operating System for Your Digital Enterprise</h1>
            <p class="hero-subtitle">Unified, scalable, and secure. Gaia Alpha empowers your team to build, manage, and
                deploy mission-critical applications with zero friction.</p>
            <div class="hero-actions">
                <a href="/<?= htmlspecialchars($globalSettings['app_slug'] ?? 'app') ?>" class="button btn-lg nav-cta">Start
                    Free Trial</a>
                <a href="#features" class="button btn-lg"
                    style="background:transparent; border:1px solid var(--border-color);">Learn More</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Trust / Logos -->
<section class="trust-section">
    <p class="text-secondary text-center mb-4">TRUSTED BY INNOVATIVE TEAMS WORLDWIDE</p>
    <div class="logo-grid">
        <!-- Placeholders for logos -->
        <div style="font-weight:700; font-size:1.5rem;">ACME Corp</div>
        <div style="font-weight:700; font-size:1.5rem;">GlobalSoft</div>
        <div style="font-weight:700; font-size:1.5rem;">Nebula Inc</div>
        <div style="font-weight:700; font-size:1.5rem;">Vertex</div>
        <div style="font-weight:700; font-size:1.5rem;">Horizon</div>
    </div>
</section>

<!-- Features -->
<section id="features" class="features-section">
    <div class="section-header">
        <h2 class="section-title">Why Gaia Alpha?</h2>
        <p class="section-desc">Everything you need to scale your infrastructure without the complexity.</p>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🚀</div>
            <h3 class="feature-title">Lightning Fast</h3>
            <p class="feature-text">Built on a lightweight PHP core with vanilla JavaScript, Gaia Alpha delivers
                sub-100ms response times for critical operations.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🛡️</div>
            <h3 class="feature-title">Enterprise Security</h3>
            <p class="feature-text">Role-based access control (RBAC), encrypted storage, and audit logging come
                standard out of the box.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🧩</div>
            <h3 class="feature-title">Modular Architecture</h3>
            <p class="feature-text">Extend functionality with a powerful plugin system. Add what you need, remove
                what you don't.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3 class="feature-title">Real-time Analytics</h3>
            <p class="feature-text">Gain insights into your application usage and performance with built-in
                dashboarding tools.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🌍</div>
            <h3 class="feature-title">Global Scale</h3>
            <p class="feature-text">Ready for internationalization and distributed deployment via Docker.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🛠️</div>
            <h3 class="feature-title">Developer First</h3>
            <p class="feature-text">Comprehensive API, clear documentation, and CLI tools aimed at developer
                productivity.</p>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="section-header" style="margin-top: 80px;">
    <div class="stats-section">
        <div class="stat-item">
            <h3>99.9%</h3>
            <p>Uptime SLA</p>
        </div>
        <div class="stat-item">
            <h3>500+</h3>
            <p>Integrations</p>
        </div>
        <div class="stat-item">
            <h3>24/7</h3>
            <p>Support</p>
        </div>
        <div class="stat-item">
            <h3>10k+</h3>
            <p>Developers</p>
        </div>
    </div>
</section>

<!-- Latest News -->
<?php if (!empty($latestPages)): ?>
    <section class="features-section" style="background: transparent;">
        <div class="section-header">
            <h2 class="section-title">Latest Updates</h2>
        </div>
        <div class="features-grid">
            <?php foreach ($latestPages as $p): ?>
                <?php if ($p['slug'] === 'home')
                    continue; ?>
                <div class="feature-card" style="padding: 0; overflow: hidden;">
                    <?php
                    $imgSrc = !empty($p['image']) ? $p['image'] : 'https://placehold.co/600x300/18181b/FFF?text=Update';
                    ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['title']) ?>"
                        style="width:100%; height:200px; object-fit:cover;">
                    <div style="padding: 24px;">
                        <h4 style="font-size:1.2rem; margin-bottom:10px;"><?= htmlspecialchars($p['title']) ?></h4>
                        <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:15px;">
                            <?= date('F j, Y', strtotime($p['created_at'])) ?>
                        </p>
                        <a href="/<?= htmlspecialchars($p['slug']) ?>"
                            style="color:var(--accent-color); text-decoration:none; font-weight:600;">Read more &rarr;</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php Part::in("site_footer") ?>