<?php

namespace GaiaAlpha;

use GaiaAlpha\Env;

class Router
{
    private static array $routes = [];

    public static function add(string $method, string $path, callable $handler)
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public static function get(string $path, callable $handler)
    {
        self::add('GET', $path, $handler);
    }
    public static function post(string $path, callable $handler)
    {
        self::add('POST', $path, $handler);
    }
    public static function put(string $path, callable $handler)
    {
        self::add('PUT', $path, $handler);
    }
    public static function patch(string $path, callable $handler)
    {
        self::add('PATCH', $path, $handler);
    }
    public static function delete(string $path, callable $handler)
    {
        self::add('DELETE', $path, $handler);
    }



    public static function dispatch(string $method, string $uri)
    {
        // Hook before dispatch
        Hook::run('router_dispatch_before', $method, $uri);

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

                // Hook when route is matched
                Hook::run('router_matched', $route, $matches);

                call_user_func_array($route['handler'], $matches);
                return true;
            }
        }

        return false;
    }

    public static function handle()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        $handled = self::dispatch($method, $uri);

        if (!$handled) {
            // Hook for 404
            Hook::run('router_404', $uri);

            if (strpos($uri, '/api/') === 0) {
                Response::json(['error' => 'API Endpoint Not Found'], 404);
            } else {
                echo "File not found";
            }
        }
    }
}
