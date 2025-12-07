<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaia Alpha</title>
    <link rel="stylesheet" href="/css/site.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: var(--space-xl) var(--space-md);
        }

        .page-header {
            margin-bottom: var(--space-xl);
            text-align: center;
        }

        .page-title {
            font-size: 3rem;
            margin-bottom: var(--space-sm);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-meta {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .page-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-primary);
        }

        .page-content img {
            max-width: 100%;
            border-radius: var(--radius-md);
            margin: var(--space-md) 0;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: var(--space-lg);
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--primary-color);
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

        <main class="page-container">
            <a href="/" class="back-link">&larr; Back to Home</a>

            <div id="page-content">
                Loading...
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const container = document.getElementById('page-content');
            const slug = window.location.pathname.split('/').pop();

            try {
                const res = await fetch(`/api/public/pages/${slug}`);
                if (!res.ok) throw new Error('Page not found');

                const page = await res.json();
                document.title = `${page.title} - Gaia Alpha`;

                container.innerHTML = `
                    <div class="page-header">
                        <h1 class="page-title">${page.title}</h1>
                        <div class="page-meta">
                            Published on ${new Date(page.created_at).toLocaleDateString()}
                        </div>
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
</body>

</html>