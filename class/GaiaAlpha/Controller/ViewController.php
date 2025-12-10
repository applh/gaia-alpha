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
        $this->render('public_home.php');
    }

    public function registerRoutes()
    {
        // App Route: /app or /app/...
        // Regex logic: Matches /app or /app/anything. Does NOT match /apple.
        // Router wraps path in #^...$#
        Router::get('/app(?:/.*)?', [$this, 'app']);

        // Public Form: /f/{slug}
        Router::get('/f/([\w-]+)/?', [$this, 'form']);

        // Single Page: /page/{slug}
        Router::get('/page/([\w-]+)/?', [$this, 'page']);

        // Catch-all (Home)
        // Must NOT match /api/ or /media/ to allow them to 404 properly if not found
        Router::get('(?!/api/|/media/).*', [$this, 'home']);
    }
}
