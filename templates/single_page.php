<?php
$page = $page ?? null;
// Variables for layout/header.php
$title = $page['title'] ?? ($globalSettings['site_title'] ?? 'Gaia Alpha');
$desc = $page['meta_description'] ?? ($globalSettings['site_description'] ?? 'Gaia Alpha - The unified open-source operating system.');
$keywords = $page['meta_keywords'] ?? ($globalSettings['site_keywords'] ?? '');

require __DIR__ . '/layout/header.php';
?>

<style>
    /* Specific styles for Single Page that aren't in header/layout */
    .page-container {
        max-width: 900px;
        margin: 0 auto;
        padding: var(--space-xl) var(--space-md);
        margin-top: 80px;
        /* Offset fixed header */
        min-height: 60vh;
    }

    .page-header {
        margin-bottom: 60px;
        text-align: center;
    }

    .page-title {
        font-size: 3.5rem;
        margin-bottom: var(--space-md);
        font-weight: 700;
        background: linear-gradient(135deg, #fff 0%, #a5a5a5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        line-height: 1.1;
    }

    .page-meta {
        color: var(--text-secondary);
        font-size: 1rem;
    }

    .page-content {
        font-size: 1.15rem;
        line-height: 1.8;
        color: var(--text-primary);
    }

    .page-content h2 {
        font-size: 2rem;
        margin-top: 2rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
    }

    .page-content h3 {
        font-size: 1.5rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
    }

    .page-content p {
        margin-bottom: 1.5rem;
        color: #a1a1aa;
    }

    .page-content ul {
        margin-bottom: 1.5rem;
        padding-left: 1.5rem;
        color: #a1a1aa;
    }

    .page-content li {
        margin-bottom: 0.5rem;
    }

    .page-content img {
        max-width: 100%;
        border-radius: var(--radius-md);
        margin: var(--space-md) 0;
        border: 1px solid var(--border-color);
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        margin-bottom: var(--space-lg);
        color: var(--text-secondary);
        text-decoration: none;
        transition: color 0.2s;
        font-weight: 500;
    }

    .back-link:hover {
        color: var(--accent-color);
    }
</style>

<div class="page-container">
    <div id="page-content">
        <?php if ($page): ?>
            <div class="page-header">
                <h1 class="page-title"><?= htmlspecialchars($page['title']) ?></h1>
                <?php if (!empty($page['image'])): ?>
                    <div style="position: relative; margin-top: 2rem;">
                        <img src="<?= htmlspecialchars($page['image']) ?>?w=1200" alt="<?= htmlspecialchars($page['title']) ?>"
                            style="width: 100%; height: auto; border-radius: 12px; max-height: 500px; object-fit: cover;">
                    </div>
                <?php endif; ?>
            </div>
            <div class="page-content">
                <?= $page['content'] ?>
            </div>
        <?php else: ?>
            Loading...
        <?php endif; ?>
    </div>
</div>

<?php if (!$page): ?>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const container = document.getElementById('page-content');
            const slug = window.location.pathname.split('/').pop();

            try {
                const res = await fetch(`/@/public/pages/${slug}`);
                if (!res.ok) throw new Error('Page not found');

                const page = await res.json();
                document.title = `${page.title} - Gaia Alpha`;

                container.innerHTML = `
                <div class="page-header">
                    <h1 class="page-title">${page.title}</h1>
                    ${page.image ? `<div style="position: relative; margin-top: 2rem;"><img src="${page.image}?w=1200" alt="${page.title}" style="width: 100%; height: auto; border-radius: 12px; max-height: 500px; object-fit: cover;"></div>` : ''}
                </div>
                <div class="page-content">
                    ${page.content}
                </div>
            `;
            } catch (e) {
                container.innerHTML = '<p style="color: var(--danger-color); text-align: center;">Page not found.</p>';
            }
        });
    </script>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>