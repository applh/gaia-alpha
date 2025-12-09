<?php

namespace GaiaAlpha;

use GaiaAlpha\Env;

class Router
{
    private static array $routes = [];

    public static function add(string $method, string $path, callable $handler)
    {
        self::$routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public static function loadControllers()
    {
        $rootDir = Env::get('root_dir');
        // Dynamically Init Controllers
        $controllers = [];
        foreach (glob($rootDir . '/class/GaiaAlpha/Controller/*Controller.php') as $file) {
            $filename = basename($file, '.php');
            if ($filename === 'BaseController')
                continue;

            $key = strtolower(str_replace('Controller', '', $filename));
            $className = "GaiaAlpha\\Controller\\$filename";

            if (class_exists($className)) {
                $controller = new $className();
                if (method_exists($controller, 'init')) {
                    $controller->init();
                }
                $controllers[$key] = $controller;
            }
        }

        Env::set('controllers', $controllers);

        // Pass an instance to satisfy type hints, even though methods are static
        $routerInstance = new self();
        foreach ($controllers as $controller) {
            $controller->registerRoutes($routerInstance);
        }
    }

    public static function dispatch(string $method, string $uri)
    {
        // Simple exact match or regex
        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert route path to regex
            // e.g., /api/todos/(\d+)
            $pattern = '#^' . $route['path'] . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                return call_user_func_array($route['handler'], $matches);
            }
        }

        return false;
    }

    public static function handle()
    {
        $rootDir = Env::get('root_dir');
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // API & Media Routing
        if (strpos($uri, '/api/') === 0 || strpos($uri, '/media/') === 0) {
            $handled = self::dispatch($method, $uri);
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
            include $rootDir . '/templates/app.php';
        } elseif (preg_match('#^/f/([\w-]+)/?$#', $uri, $matches)) {
            $slug = $matches[1];
            include $rootDir . '/templates/public_form.php';
        } elseif (preg_match('#^/page/([\w-]+)/?$#', $uri, $matches)) {
            $slug = $matches[1];
            include $rootDir . '/templates/single_page.php';
        } else {
            include $rootDir . '/templates/public_home.php';
        }
    }
}
