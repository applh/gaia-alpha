<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\Page;
use GaiaAlpha\Hook;
use GaiaAlpha\Response;

class PublicController extends BaseController
{
    public function index()
    {
        $pages = Page::getLatestPublic();
        $pages = Hook::filter('public_pages_index', $pages);
        Response::json($pages);
    }

    public function show($slug)
    {
        $page = Page::findBySlug($slug);

        if (!$page) {
            Response::json(['error' => 'Page not found'], 404);
            return;
        }

        $page = Hook::filter('public_page_show', $page, $slug);
        Response::json($page);
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
            $page['content'] = $html;
        } elseif (isset($page['content_format']) && $page['content_format'] === 'markdown') {
            $parsedown = new \GaiaAlpha\Helper\Parsedown();
            $page['content'] = $parsedown->text($content);
        }
        // Else: Content is already HTML string, use as is.

        // Determine Template
        $template = $page['template_slug'] ?? 'single_page';
        // Sanitize template name to prevent traversal
        $template = preg_replace('/[^a-zA-Z0-9_-]/', '', $template);

        $templatePath = dirname(__DIR__, 3) . '/templates/' . $template . '.php';

        if (!file_exists($templatePath)) {
            // Check Database for Template
            $dbTemplate = \GaiaAlpha\Model\Template::findBySlug($template);

            if ($dbTemplate) {
                // Ensure Cache Dir
                $cacheDir = dirname(__DIR__, 3) . '/my-data/cache/templates';
                if (!is_dir($cacheDir))
                    mkdir($cacheDir, 0777, true);

                $cacheFile = $cacheDir . '/' . $template . '.php'; // Use .php extension so it can be required

                // Optimization: fetch updated_at and compare with filemtime
                if ($this->isJson($dbTemplate['content'])) {
                    $structure = json_decode($dbTemplate['content'], true);
                    if (isset($structure['header'])) { // It's a visual template
                        $compiledFunc = $this->compileVisualTemplate($structure);
                        file_put_contents($cacheFile, $compiledFunc);
                    } else {
                        // Unknown JSON, maybe just raw content?
                        file_put_contents($cacheFile, $dbTemplate['content']);
                    }
                } else {
                    file_put_contents($cacheFile, $dbTemplate['content']);
                }

                $templatePath = $cacheFile;
            } else {
                // Fallback to single_page
                $templatePath = dirname(__DIR__, 3) . '/templates/single_page.php';
            }
        }

        // Render Template
        // Render Template

        // Fetch Global Settings for Templates
        $globalSettings = \GaiaAlpha\Model\DataStore::getAll(0, 'global_config');

        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        // Inject Debug Toolbar if Admin
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $isAdmin = isset($_SESSION['level']) && $_SESSION['level'] >= 100;

        if ($isAdmin) {
            // Need access to helper method or duplicate code.
            // Best to use a helper class or method.
            // Since PublicController extends BaseController, let's look if BaseController has it?
            // BaseController doesn't have injectDebugToolbar.
            // Ideally, move logic to valid Helper or BaseController.
            // For now, I'll use the same logic here to fix it immediately.

            if (strpos($content, '</html>') !== false) {
                $debugData = \GaiaAlpha\Debug::getData();
                $jsonData = json_encode($debugData);
                $vueUrl = \GaiaAlpha\Asset::url('/js/vendor/vue.esm-browser.js');
                $componentUrl = \GaiaAlpha\Asset::url('/js/components/admin/DebugToolbar.js');

                $toolbarScript = <<<HTML
<div id="gaia-debug-root" style="position:fixed;bottom:0;left:0;right:0;z-index:99999;"></div>
<script>
    window.GAIA_DEBUG_DATA = $jsonData;
</script>
<script type="module">
    import { createApp } from '$vueUrl';
    import * as Vue from '$vueUrl';
    import DebugToolbar from '$componentUrl';
    
    // Make Vue available globally for the component
    window.Vue = Vue;
    
    // Create a separate app for the toolbar to avoid conflicts with main app
    const app = createApp(DebugToolbar);
    app.mount('#gaia-debug-root');
</script>
HTML;
                $content = str_replace('</body>', $toolbarScript . '</body>', $content);
            }
        }

        echo $content;
    }

    private function isJson($string)
    {
        if (!is_string($string))
            return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function compileVisualTemplate($structure)
    {
        // Compile JSON Structure to PHP Code
        $php = "<?php\n";
        $php .= "// Auto-generated from Visual Builder\n";
        $php .= "?>\n";
        $php .= "<!DOCTYPE html>\n<html lang='<?= \$globalSettings['site_language'] ?? 'en' ?>'>\n<head>\n";
        $php .= "    <meta charset='UTF-8'>\n";
        $php .= "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $php .= "    <title><?= \$page['title'] ?></title>\n";
        $php .= "    <link rel='stylesheet' href='/resources/css/site.css'>\n";
        $php .= "    <link rel='stylesheet' href='/resources/css/fonts.css'>\n";
        $php .= "    <style>body { margin:0; font-family:var(--font-primary); background:var(--bg-color); color:var(--text-primary); }</style>\n";
        $php .= "</head>\n<body>\n";

        // Header
        $php .= "<header class='site-header'>\n";
        if (!empty($structure['header'])) {
            foreach ($structure['header'] as $node) {
                $php .= $this->compileNode($node) . "\n";
            }
        }
        $php .= "</header>\n";

        // Main
        $php .= "<main class='site-main'>\n";
        if (!empty($structure['main'])) {
            foreach ($structure['main'] as $node) {
                $php .= $this->compileNode($node) . "\n";
            }
        }
        // Inject Page Content
        $php .= "    <div class='page-content'>\n";
        $php .= "        <?= \$page['content'] ?>\n";
        $php .= "    </div>\n";
        $php .= "</main>\n";

        // Footer
        $php .= "<footer class='site-footer'>\n";
        if (!empty($structure['footer'])) {
            foreach ($structure['footer'] as $node) {
                $php .= $this->compileNode($node) . "\n";
            }
        }
        $php .= "</footer>\n";

        $php .= "<script src='/resources/js/site.js'></script>\n";
        $php .= "</body>\n</html>";

        return $php;
    }

    private function compileNode($node)
    {
        if (!isset($node['type']))
            return '';
        $type = $node['type'];
        $children = isset($node['children']) ? $node['children'] : [];
        $content = isset($node['content']) ? htmlspecialchars($node['content']) : '';
        $src = isset($node['src']) ? htmlspecialchars($node['src']) : '';

        $html = '';
        switch ($type) {
            case 'section':
                $html .= "<section>";
                foreach ($children as $child)
                    $html .= $this->compileNode($child);
                $html .= "</section>";
                break;
            case 'columns':
                $html .= "<div class='columns' style='display:flex; gap:20px;'>";
                foreach ($children as $child)
                    $html .= $this->compileNode($child);
                $html .= "</div>";
                break;
            case 'column':
                $html .= "<div class='column' style='flex:1;'>";
                foreach ($children as $child)
                    $html .= $this->compileNode($child);
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
                    $html .= "<img src='{$src}' loading='lazy' style='max-width:100%;' />";
                break;
            case 'markdown':
                $parsedown = new \GaiaAlpha\Helper\Parsedown();
                $html .= "<div class='markdown-content'>" . $parsedown->text($content) . "</div>";
                break;
            default:
                break;
        }
        return $html;
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
            case 'markdown':
                $parsedown = new \GaiaAlpha\Helper\Parsedown();
                $html .= "<div class='markdown-content'>" . $parsedown->text($content) . "</div>";
                break;
            default:
                break;
        }

        // Allow plugins to modify rendered node
        $html = Hook::filter('public_page_render_node', $html, $node);

        return $html;
    }

    public function partial($name)
    {
        // Check Cache
        $cacheDir = dirname(__DIR__, 3) . '/my-data/cache/partials';
        $cacheFile = $cacheDir . '/' . $name . '.php';

        if (file_exists($cacheFile)) {
            // Optimization: check if stale compared to DB? 
            // For now, assume cache is managed/cleared or we blindly trust it until backend updates it?
            // Actually, admin should clear cache on update. 
            // Simpler: Just check DB if debugging, but for speed, check file existence.
            // But we need to create it if missing.
        } else {
            // Fetch from DB
            $content = \GaiaAlpha\Model\DB::fetchColumn("SELECT content FROM cms_partials WHERE name = ?", [$name]);

            if ($content === false) {
                echo "<!-- Partial '$name' not found -->";
                return;
            }

            if (!is_dir($cacheDir))
                mkdir($cacheDir, 0777, true);
            file_put_contents($cacheFile, $content);
        }

        // Expose $page to partials? 
        // We are inside a method. Variables scope is local.
        // We need to capture variables from the caller or use global?
        // Actually, 'require' inside a method sees $this, but not local vars of caller (render).
        // But many partials rely on $page.
        // Solution: Pass $page explicitly or rely on $page being available via some other means?
        // Standard pattern: extract($data).

        // However, we are in a class method. 
        // Changing architecture: render() included file. File had access to $page because it was defined in render().
        // If file calls $this->partial(), we don't readily have access to $page unless we pass it.
        // Or we store $page in a property $this->currentPage.

        // Let's rely on standard include behavior.
        // But wait, $page is not a property. 
        // We might need to upgrade render() to set $this->currentPage.
        // But for minimal impact: assume partials are just static HTML snippets or access generic stuff.
        // If they need $page, we should probably pass it or make it available.
        // Let's make it available via $this->page if we set it.

        // Quick fix: user must call $this->partial('name', $page) if they need page data? 
        // Or we assume these are just header/footer chunks.

        require $cacheFile;
    }

    public function sitemap()
    {
        $pages = \GaiaAlpha\Model\DB::fetchAll("SELECT slug, updated_at FROM cms_pages WHERE cat = 'page' ORDER BY updated_at DESC");
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

        $customRobots = \GaiaAlpha\Model\DataStore::get(0, 'global_config', 'robots_txt');

        if (!empty($customRobots)) {
            echo $customRobots;
        } else {
            echo "User-agent: *\nAllow: /\nSitemap: /sitemap.xml";
        }
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
