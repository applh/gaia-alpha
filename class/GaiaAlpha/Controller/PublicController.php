<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\Page;
use GaiaAlpha\Hook;

class PublicController extends BaseController
{
    public function index()
    {
        $pages = Page::getLatestPublic();
        $pages = Hook::filter('public_pages_index', $pages);
        $this->jsonResponse($pages);
    }

    public function show($slug)
    {
        $page = Page::findBySlug($slug);

        if (!$page) {
            $this->jsonResponse(['error' => 'Page not found'], 404);
            return;
        }

        $page = Hook::filter('public_page_show', $page, $slug);
        $this->jsonResponse($page);
    }

    public function render($slug)
    {
        $page = Page::findBySlug($slug);

        if (!$page) {
            http_response_code(404);
            // Optional: load a 404 template if exists
            echo "Page not found";
            return;
        }

        // Check if content is JSON (Structure) or HTML
        $content = $page['content'];
        $structure = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($structure)) {
            // Render Structure to HTML string
            $html = '';

            // Allow plugins to inject content before structure
            ob_start();
            Hook::run('public_page_render_header', $page);
            $html .= ob_get_clean();

            if (isset($structure['header'])) {
                $html .= "<header class='site-header'>";
                foreach ($structure['header'] as $node)
                    $html .= $this->renderNode($node);
                $html .= "</header>";
            }

            if (isset($structure['main'])) {
                $html .= "<main class='site-main'>";
                foreach ($structure['main'] as $node)
                    $html .= $this->renderNode($node);
                $html .= "</main>";
            }

            if (isset($structure['footer'])) {
                $html .= "<footer class='site-footer'>";
                foreach ($structure['footer'] as $node)
                    $html .= $this->renderNode($node);
                $html .= "</footer>";
            }

            // Allow plugins to inject content after structure
            ob_start();
            Hook::run('public_page_render_footer', $page);
            $html .= ob_get_clean();

            $page['content'] = $html;
        }
        // Else: Content is already HTML string, use as is.

        // Determine Template
        $template = $page['template_slug'] ?? 'single_page';
        // Sanitize template name to prevent traversal
        $template = preg_replace('/[^a-zA-Z0-9_-]/', '', $template);

        $templatePath = dirname(__DIR__, 3) . '/templates/' . $template . '.php';

        if (!file_exists($templatePath)) {
            $templatePath = dirname(__DIR__, 3) . '/templates/single_page.php';
        }

        // Render Template
        require $templatePath;
    }

    private function renderNode($node)
    {
        if (!isset($node['type']))
            return '';

        $type = $node['type'];
        $children = isset($node['children']) ? $node['children'] : [];
        $content = isset($node['content']) ? htmlspecialchars($node['content']) : '';

        // Use src for images
        $src = isset($node['src']) ? htmlspecialchars($node['src']) : '';

        $html = '';

        switch ($type) {
            case 'section':
                $html .= "<section>";
                foreach ($children as $child)
                    $html .= $this->renderNode($child);
                $html .= "</section>";
                break;
            case 'columns':
                $html .= "<div class='col-container'>";
                foreach ($children as $child)
                    $html .= $this->renderNode($child);
                $html .= "</div>";
                break;
            case 'column':
                $html .= "<div class='col'>";
                foreach ($children as $child)
                    $html .= $this->renderNode($child);
                $html .= "</div>";
                break;
            case 'div':
                $html .= "<div>";
                foreach ($children as $child)
                    $html .= $this->renderNode($child);
                $html .= "</div>";
                break;
            case 'h1':
                $html .= "<h1>{$content}</h1>";
                break;
            case 'h2':
                $html .= "<h2>{$content}</h2>";
                break;
            case 'h3':
                $html .= "<h3>{$content}</h3>";
                break;
            case 'p':
                $html .= "<p>{$content}</p>";
                break;
            case 'image':
                if ($src)
                    $html .= "<img src='{$src}' loading='lazy' />";
                else
                    $html .= "<div style='background:#eee; padding:20px; text-align:center'>Image Placeholder</div>";
                break;
            default:
                break;
        }

        // Allow plugins to modify rendered node
        $html = Hook::filter('public_page_render_node', $html, $node);

        return $html;
    }

    public function sitemap()
    {
        $pdo = \GaiaAlpha\Controller\DbController::getPdo();
        $stmt = $pdo->query("SELECT slug, updated_at FROM cms_pages WHERE cat='page'");
        $pages = $stmt->fetchAll();

        header("Content-Type: application/xml");
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

        // Add Home
        echo '<url><loc>' . $baseUrl . '/</loc><changefreq>daily</changefreq></url>';

        foreach ($pages as $p) {
            if ($p['slug'] === 'home')
                continue;
            echo '<url>';
            echo '<loc>' . $baseUrl . '/' . $p['slug'] . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($p['updated_at'])) . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '</url>';
        }
        echo '</urlset>';
    }

    public function robots()
    {
        header("Content-Type: text/plain");
        echo "User-agent: *\nAllow: /\nSitemap: /sitemap.xml";
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/public/pages', [$this, 'index']);
        \GaiaAlpha\Router::add('GET', '/@/public/pages/([\w-]+)', [$this, 'show']);

        // SEO Routes
        \GaiaAlpha\Router::add('GET', '/sitemap.xml', [$this, 'sitemap']);
        \GaiaAlpha\Router::add('GET', '/robots.txt', [$this, 'robots']);

        // Public HTML Views
        \GaiaAlpha\Router::add('GET', '/page/([\w-]+)', [$this, 'render']);
    }
}
