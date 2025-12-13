<?php
// templates/layout/footer.php
$footerMenu = \GaiaAlpha\Model\Menu::findByLocation('footer');
$footerItems = $footerMenu ? json_decode($footerMenu['items'], true) : [];
?>
</main>

<!-- Footer -->
<style>
    .site-footer {
        padding: 80px 20px 40px;
        border-top: 1px solid var(--border-color);
        background: #050507;
        margin-top: auto;
        /* Push to bottom if content is short */
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 40px;
        max-width: 1200px;
        margin: 0 auto 60px;
    }

    .footer-brand h2 {
        margin-bottom: 20px;
        font-size: 1.5rem;
        color: var(--text-primary);
    }

    .footer-col h4 {
        color: var(--text-primary);
        margin-bottom: 20px;
        font-weight: 600;
    }

    .footer-link {
        display: block;
        color: var(--text-secondary);
        text-decoration: none;
        margin-bottom: 12px;
        transition: color 0.2s;
    }

    .footer-link:hover {
        color: var(--text-primary);
    }

    .footer-bottom {
        max-width: 1200px;
        margin: 0 auto;
        padding-top: 40px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-col footer-brand">
            <h2>Gaia Alpha</h2>
            <p class="text-secondary" style="max-width: 300px;">The open-source operating system for the modern web.
                Empowering builders to create without limits.</p>
        </div>
        <div class="footer-col">
            <h4>Product</h4>
            <a href="#" class="footer-link">Features</a>
            <a href="#" class="footer-link">Integration</a>
            <a href="#" class="footer-link">Enterprise</a>
            <a href="#" class="footer-link">Changelog</a>
        </div>
        <div class="footer-col">
            <h4>Resources</h4>
            <a href="#" class="footer-link">Documentation</a>
            <a href="#" class="footer-link">API Reference</a>
            <a href="#" class="footer-link">Community</a>
            <a href="#" class="footer-link">Blog</a>
        </div>
        <div class="footer-col">
            <h4>Company</h4>
            <?php foreach ($footerItems as $item): ?>
                <a href="<?= htmlspecialchars($item['href']) ?>"
                    class="footer-link"><?= htmlspecialchars($item['text']) ?></a>
            <?php endforeach; ?>
            <a href="/page/contact" class="footer-link">Contact</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> Gaia Alpha. All rights reserved.</span>
        <div style="display: flex; gap: 20px;">
            <a href="#" style="color:inherit; text-decoration:none;">Twitter</a>
            <a href="#" style="color:inherit; text-decoration:none;">GitHub</a>
            <a href="#" style="color:inherit; text-decoration:none;">Discord</a>
        </div>
    </div>
</footer>
</body>

</html>