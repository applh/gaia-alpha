<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Router;
use GaiaAlpha\Env;
use GaiaAlpha\Controller\BaseController;

class ViewController extends BaseController
{
    public function getRank()
    {
        return 100;
    }

    private function render($template, $vars = [])
    {
        $rootDir = Env::get('root_dir');
        extract($vars);

        // Remove .php extension if present for DB lookup
        $templateSlug = str_replace('.php', '', $template);
        $templatePath = $rootDir . '/templates/' . $template;

        // Check if file exists
        if (!file_exists($templatePath)) {
            // Check Database for Template
            $dbTemplate = \GaiaAlpha\Model\Template::findBySlug($templateSlug);

            if ($dbTemplate) {
                // Ensure Cache Dir
                $site = \GaiaAlpha\SiteManager::getCurrentSite() ?? 'default';
                $cacheDir = $rootDir . '/my-data/cache/templates/' . $site;
                if (!is_dir($cacheDir))
                    mkdir($cacheDir, 0777, true);

                $cacheFile = $cacheDir . '/' . $templateSlug . '.php';

                // Check if content is JSON (Visual Template)
                if ($this->isJson($dbTemplate['content'])) {
                    $structure = json_decode($dbTemplate['content'], true);
                    if (isset($structure['header'])) {
                        $compiledFunc = $this->compileVisualTemplate($structure);
                        file_put_contents($cacheFile, $compiledFunc);
                    } else {
                        file_put_contents($cacheFile, $dbTemplate['content']);
                    }
                } else {
                    file_put_contents($cacheFile, $dbTemplate['content']);
                }

                $templatePath = $cacheFile;
            }
            // If still not found, templatePath will fail on include and show error
        }

        // Fetch Global Settings
        $globalSettings = \GaiaAlpha\Model\DataStore::getAll(0, 'global_config');

        // Start output buffering
        ob_start();
        include $templatePath;
        $content = ob_get_clean();

        // Inject Debug Toolbar if Admin
        // Check session safely
        \GaiaAlpha\Session::start();

        $isAdmin = \GaiaAlpha\Session::isAdmin();

        if ($isAdmin) {
            $content = $this->injectDebugToolbar($content);
        }

        echo $content;
    }

    private function injectDebugToolbar($content)
    {
        // Don't inject if not HTML
        if (strpos($content, '</html>') === false) {
            return $content;
        }

        $vueUrl = \GaiaAlpha\Asset::url('/js/vendor/vue.esm-browser.js');
        $timestamp = time() + 2; // Cache busting updated again
        $componentUrl = \GaiaAlpha\Asset::url('/js/components/admin/DebugToolbar.js?v=' . $timestamp);

        // Use placeholder for late injection (handled by Debug::injectHeader via response_send hook)
        // This ensures all tasks (including step99 flush) are captured.
        // Using a valid JS string prevents SyntaxError if replacement fails.
        $toolbarScript = <<<HTML
<div id="gaia-debug-root" style="position:fixed;bottom:0;left:0;right:0;z-index:99999;"></div>
<script>
    window.GAIA_DEBUG_DATA = "__GAIA_DEBUG_DATA_PLACEHOLDER__";
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

        return str_replace('</body>', $toolbarScript . '</body>', $content);
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
        $php = "<?php\n// Auto-generated from Visual Builder\n?>\n";
        $php .= "<!DOCTYPE html>\n<html lang='en'>\n<head>\n";
        $php .= "    <meta charset='UTF-8'>\n";
        $php .= "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $php .= "    <title><?= \$page['title'] ?? 'Page' ?></title>\n";
        $php .= "    <link rel='stylesheet' href='/min/css/site.css'>\n";
        $php .= "</head>\n<body>\n";

        if (!empty($structure['header'])) {
            $php .= "<header class='site-header'>\n";
            foreach ($structure['header'] as $node) {
                $php .= $this->compileNode($node) . "\n";
            }
            $php .= "</header>\n";
        }

        $php .= "<main class='site-main'>\n";
        if (!empty($structure['main'])) {
            foreach ($structure['main'] as $node) {
                $php .= $this->compileNode($node) . "\n";
            }
        }
        $php .= "    <div class='page-content'>\n";
        $php .= "        <?= \$page['content'] ?>\n";
        $php .= "    </div>\n";
        $php .= "</main>\n";

        if (!empty($structure['footer'])) {
            $php .= "<footer class='site-footer'>\n";
            foreach ($structure['footer'] as $node) {
                $php .= $this->compileNode($node) . "\n";
            }
            $php .= "</footer>\n";
        }

        $php .= "</body>\n</html>";
        return $php;
    }

    private function compileNode($node)
    {
        if (!isset($node['type']))
            return '';
        $type = $node['type'];
        $children = $node['children'] ?? [];
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
        }
        return $html;
    }

    public function app()
    {
        $this->render('app.php');
    }

    public function form($slug)
    {
        $this->render('public_form.php', ['slug' => $slug]);
    }

    public function page($slug)
    {
        $this->render('single_page.php', ['slug' => $slug]);
    }

    public function home()
    {
        // 1. Determine slug
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Remove leading slash
        $slug = ltrim($uri, '/');

        // Handle root
        if ($slug === '' || $slug === 'index.php') {
            // Try to find a page named 'home'
            $slug = 'home';
        }

        // 2. Lookup Page
        // Need to ensure DB connection if not already
        \GaiaAlpha\Model\DB::connect();
        $page = \GaiaAlpha\Model\Page::findBySlug($slug);

        if ($page) {
            // 3. Render Template
            // If template_slug is set, use it. Otherwise default to single_page?
            // User asked for "set the /app page to use app template"
            // So we trust the template_slug from DB.
            $template = $page['template_slug'] ?? 'single_page.php';

            // Ensure .php extension
            if (substr($template, -4) !== '.php') {
                $template .= '.php';
            }

            // Render
            $this->render($template, ['page' => $page, 'slug' => $slug]);
        } else {
            // 4. Fallback
            if ($slug === 'home') {
                // If no 'home' page in DB, render default public home
                $this->render('public_home.php');
            } else {
                // 404
                http_response_code(404);
                echo "Page not found";
            }
        }
    }

    public function registerRoutes()
    {
        // Catch-all (Home)
        // Must NOT match /@/, /media/, /js/, /css/ to allow them to 404 properly if not found
        // Also excluding /min/ and /assets/ as used in AssetController
        Router::get('(?!/@/|/media/|/min/|/assets/).*', [$this, 'home']);
    }
}
