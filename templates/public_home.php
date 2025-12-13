<?php
$title = 'Gaia Alpha - Home';
$desc = 'A modern, lightweight platform for your content.';
require __DIR__ . '/layout/header.php';
?>

<style>
    /* Specific styles for Public Home that aren't in header/layout */
    :root {
        --hero-height: 50vh;
    }

    .hero-section {
        min-height: var(--hero-height);
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 80px 20px;
        position: relative;
        overflow: hidden;
    }

    .hero-title {
        font-size: 3.5rem;
        margin-bottom: var(--space-md);
        font-weight: 700;
        background: linear-gradient(135deg, #fff 0%, #a5a5a5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: var(--space-lg);
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 var(--space-md);
    }

    .card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow: hidden;
        transition: transform 0.2s;
        backdrop-filter: blur(20px);
        display: flex;
        flex-direction: column;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .card-img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        background: rgba(0, 0, 0, 0.2);
    }

    .card-body {
        padding: var(--space-md);
        flex: 1;
    }

    .card-title {
        font-size: 1.25rem;
        margin-bottom: var(--space-xs);
        color: var(--text-primary);
    }

    .card-meta {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }
</style>

<section class="hero-section">
    <div style="max-width: 800px;">
        <h1 class="hero-title">Welcome to Gaia Alpha</h1>
        <p style="font-size: 1.25rem; color: var(--text-secondary); margin-bottom: 2rem;">A modern, lightweight platform
            for your content.</p>
        <a href="/app" role="button" class="nav-cta" style="font-size: 1.1rem; padding: 12px 32px;">Get Started</a>
    </div>
</section>

<section style="padding: 60px 0;">
    <h2 style="text-align: center; margin-bottom: 40px;">Latest Community Pages</h2>
    <div class="grid" id="pages-grid">
        <!-- Content via JS -->
        <div style="text-align:center; grid-column: 1/-1;">Loading...</div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const grid = document.getElementById('pages-grid');
        try {
            const res = await fetch('/@/public/pages');
            const pages = await res.json();

            if (pages.length === 0) {
                grid.innerHTML = '<p style="text-align:center; grid-column: 1/-1; color: var(--text-secondary);">No pages published yet.</p>';
                return;
            }

            grid.innerHTML = pages.map(page => {
                const imgSrc = page.image ? `${page.image}?w=600&h=400&fit=cover` : 'https://placehold.co/600x400/1e222d/FFF?text=Gaia+Alpha';

                return `
                    <article class="card">
                        <a href="/page/${page.slug}" style="text-decoration: none; color: inherit;">
                            <img src="${imgSrc}" alt="${page.title}" class="card-img">
                                <div class="card-body">
                                    <h3 class="card-title">${page.title}</h3>
                                    <div class="card-meta">
                                        <span>${new Date(page.created_at).toLocaleDateString()}</span>
                                    </div>
                                </div>
                        </a>
                    </article >
                `;
            }).join('');

        } catch (e) {
            console.error(e);
            grid.innerHTML = '<p style="color: var(--danger-color); text-align:center; grid-column: 1/-1;">Failed to load pages.</p>';
        }
    });
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>