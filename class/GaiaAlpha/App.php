<?php

namespace GaiaAlpha;

class App
{
    private Database $db;
    private Media $media;
    private Router $router;

    public static string $rootDir;

    public function __construct(string $rootDir)
    {
        self::$rootDir = $rootDir;

        $dataPath = defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : self::$rootDir . '/my-data';

        $dsn = defined('GAIA_DB_DSN') ? GAIA_DB_DSN : 'sqlite:' . (defined('GAIA_DB_PATH') ? GAIA_DB_PATH : $dataPath . '/database.sqlite');
        $this->db = new Database($dsn);
        $this->db->ensureSchema();
        $this->media = new Media($dataPath);
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
        $this->router->add('GET', '/api/todos/(\d+)/children', [$todo, 'getChildren']);

        // Admin
        $this->router->add('GET', '/api/admin/users', [$admin, 'index']);
        $this->router->add('POST', '/api/admin/users', [$admin, 'create']);
        $this->router->add('PATCH', '/api/admin/users/(\d+)', [$admin, 'update']);
        $this->router->add('DELETE', '/api/admin/users/(\d+)', [$admin, 'delete']);
        $this->router->add('GET', '/api/admin/stats', [$admin, 'stats']);

        // Database Management
        $this->router->add('GET', '/api/admin/db/tables', [$admin, 'getTables']);
        $this->router->add('GET', '/api/admin/db/table/(\w+)', [$admin, 'getTableData']);
        $this->router->add('POST', '/api/admin/db/query', [$admin, 'executeQuery']);
        $this->router->add('POST', '/api/admin/db/table/(\w+)', [$admin, 'createRecord']);
        $this->router->add('PATCH', '/api/admin/db/table/(\w+)/(\d+)', [$admin, 'updateRecord']);
        $this->router->add('DELETE', '/api/admin/db/table/(\w+)/(\d+)', [$admin, 'deleteRecord']);

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
            include self::$rootDir . '/templates/app.php';
        } elseif (preg_match('#^/page/([\w-]+)/?$#', $uri, $matches)) {
            $slug = $matches[1];
            include self::$rootDir . '/templates/single_page.php';
        } else {
            include self::$rootDir . '/templates/public_home.php';
        }
    }
}
