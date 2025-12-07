<?php

namespace GaiaAlpha;

class App
{
    private Database $db;
    private Media $media;



    private Router $router;

    public function __construct()
    {
        $this->db = new Database(__DIR__ . '/../../my-data/database.sqlite');
        $this->db->ensureSchema();
        $this->media = new Media(__DIR__ . '/../../my-data');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->router = new Router();
        $this->registerRoutes();
    }

    private function registerRoutes()
    {
        // Dependencies
        $db = $this->db;

        // Controllers
        $auth = new Controller\AuthController($db);
        $todo = new Controller\TodoController($db);
        $admin = new Controller\AdminController($db);
        $cms = new Controller\CmsController($db);
        $public = new Controller\PublicController($db);

        // Auth
        $this->router->add('POST', '/api/login', [$auth, 'login']);
        $this->router->add('POST', '/api/register', [$auth, 'register']);
        $this->router->add('POST', '/api/logout', [$auth, 'logout']);
        $this->router->add('GET', '/api/user', [$auth, 'me']);

        // Todos
        $this->router->add('GET', '/api/todos', [$todo, 'index']);
        $this->router->add('POST', '/api/todos', [$todo, 'create']);
        $this->router->add('PATCH', '/api/todos/(\d+)', [$todo, 'update']);
        $this->router->add('DELETE', '/api/todos/(\d+)', [$todo, 'delete']);

        // Admin
        $this->router->add('GET', '/api/admin/users', [$admin, 'index']);
        $this->router->add('POST', '/api/admin/users', [$admin, 'create']);
        $this->router->add('PATCH', '/api/admin/users/(\d+)', [$admin, 'update']);
        $this->router->add('DELETE', '/api/admin/users/(\d+)', [$admin, 'delete']);
        $this->router->add('GET', '/api/admin/stats', [$admin, 'stats']);

        // CMS
        $this->router->add('GET', '/api/cms/pages', [$cms, 'index']);
        $this->router->add('POST', '/api/cms/pages', [$cms, 'create']);
        $this->router->add('PATCH', '/api/cms/pages/(\d+)', [$cms, 'update']);
        $this->router->add('DELETE', '/api/cms/pages/(\d+)', [$cms, 'delete']);
        $this->router->add('POST', '/api/cms/upload', [$cms, 'upload']);

        // Public API
        $this->router->add('GET', '/api/public/pages', [$public, 'index']);
        $this->router->add('GET', '/api/public/pages/([\w-]+)', [$public, 'show']);
    }

    public function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // API Routing
        if (strpos($uri, '/api/') === 0) {
            $handled = $this->router->dispatch($method, $uri);
            if (!$handled) {
                http_response_code(404);
                echo json_encode(['error' => 'API Endpoint Not Found']);
            }
            return;
        }

        // Media Routing
        if (preg_match('#^/media/(\d+)/(.+)$#', $uri, $matches)) {
            $this->media->handleRequest($matches[1], $matches[2], $_GET);
            return;
        }

        // Frontend Routing
        if ($uri === '/app' || strpos($uri, '/app/') === 0) {
            include dirname(__DIR__, 2) . '/templates/app.php';
        } elseif (preg_match('#^/page/([\w-]+)/?$#', $uri, $matches)) {
            $slug = $matches[1];
            include dirname(__DIR__, 2) . '/templates/single_page.php';
        } else {
            include dirname(__DIR__, 2) . '/templates/public_home.php';
        }
    }
}
