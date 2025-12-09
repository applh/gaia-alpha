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
        // Dynamically Init Controllers
        self::$controllers = [];
        foreach (glob(self::$rootDir . '/class/GaiaAlpha/Controller/*Controller.php') as $file) {
            $filename = basename($file, '.php');
            if ($filename === 'BaseController')
                continue;

            $key = strtolower(str_replace('Controller', '', $filename));
            $className = "GaiaAlpha\\Controller\\$filename";

            if (class_exists($className)) {
                self::$controllers[$key] = new $className($db);
            }
        }

        foreach (self::$controllers as $controller) {
            $controller->registerRoutes($this->router);
        }

        // Register Media Route
        $this->router->add('GET', '/media/(\d+)/(.+)', function ($userId, $filename) {
            $this->media->handleRequest($userId, $filename, $_GET);
            return true; // Ensure handled is true
        });
    }

    public function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // API Routing
        // API & Media Routing
        if (strpos($uri, '/api/') === 0 || strpos($uri, '/media/') === 0) {
            $handled = $this->router->dispatch($method, $uri);
            if (!$handled) {
                http_response_code(404);
                if (strpos($uri, '/api/') === 0) {
                    echo json_encode(['error' => 'API Endpoint Not Found']);
                } else {
                    echo "File not found";
                }
            }
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
