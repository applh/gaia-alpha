<?php

namespace GaiaAlpha;

class App
{
    private Database $db;
    private Media $media;
    private Router $router;

    public static string $rootDir;
    public static array $controllers = [];

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

        // Init Controllers
        self::$controllers = [
            'auth' => new Controller\AuthController($db),
            'todo' => new Controller\TodoController($db),
            'admin' => new Controller\AdminController($db),
            'cms' => new Controller\CmsController($db),
            'form' => new Controller\FormController($db),
            'public' => new Controller\PublicController($db),
            'settings' => new Controller\SettingsController($db)
        ];

        foreach (self::$controllers as $controller) {
            $controller->registerRoutes($this->router);
        }
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
        } elseif (preg_match('#^/f/([\w-]+)/?$#', $uri, $matches)) {
            $slug = $matches[1];
            include self::$rootDir . '/templates/public_form.php';
        } elseif (preg_match('#^/page/([\w-]+)/?$#', $uri, $matches)) {
            $slug = $matches[1];
            include self::$rootDir . '/templates/single_page.php';
        } else {
            include self::$rootDir . '/templates/public_home.php';
        }
    }
}
