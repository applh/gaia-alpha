<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaia Alpha - Home</title>
    <link rel="stylesheet" href="/css/site.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .hero {
            text-align: center;
            padding: 4rem 1rem;
            background: linear-gradient(135deg, rgba(108, 92, 231, 0.1) 0%, rgba(0, 184, 148, 0.1) 100%);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-xl);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: var(--space-md);
        }

        .hero p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: var(--space-lg);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--space-lg);
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: transform 0.2s;
            backdrop-filter: blur(20px);
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
</head>

<body>
    <div id="app">
        <header>
            <a href="/" style="text-decoration: none; color: inherit;">
                <h1>Gaia Alpha</h1>
            </a>
            <nav>
                <a href="/app" class="button">Login / App</a>
            </nav>
        </header>

        <section class="hero">
            <h1>Welcome to Gaia Alpha</h1>
            <p>A modern, lightweight platform for your content.</p>
            <a href="/app" role="button" style="display:inline-block; text-decoration:none;">Get Started</a>
        </section>

        <section>
            <h2 style="margin-bottom: var(--space-lg);">Latest Community Pages</h2>
            <div class="grid" id="pages-grid">
                <!-- Content via JS -->
                <div style="text-align:center; grid-column: 1/-1;">Loading...</div>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const grid = document.getElementById('pages-grid');
            try {
                const res = await fetch('/api/public/pages');
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
</body>

</html>