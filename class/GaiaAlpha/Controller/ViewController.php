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
        // Extract vars to local scope if needed, though the templates seem to use $slug directly or generic globals?
        // Original code:
        // $slug = $matches[1];
        // include ...

        // So we need to expose $slug to the included file if it expects it.
        extract($vars);

        include $rootDir . '/templates/' . $template;
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
        \GaiaAlpha\Controller\DbController::connect();
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
