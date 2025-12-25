<?php

namespace GaiaAlpha;

use GaiaAlpha\Env;
use GaiaAlpha\Request;

class Router
{
    private static array $staticRoutes = [];
    private static array $dynamicRoutes = [];

    public static function add(string $method, string $path, callable $handler)
    {
        $method = strtoupper($method);
        // Check for regex characters
        // Note: This is a simplistic check. FastRoute uses more complex parsing.
        // Assuming standard regex delimiters are not used in simple paths, but user might use them.
        // If path contains [, (, *, ?, + it is likely dynamic.
        if (preg_match('/[\[\(\*\?\+]/', $path)) {
            self::$dynamicRoutes[$method][] = [
                'regex' => '#^' . $path . '$#',
                'handler' => $handler,
                'path' => $path // Keep original path for hooks if needed
            ];
        } else {
            self::$staticRoutes[$method][$path] = $handler;
        }

        // Keep legacy array if needed for backward compatibility or simple listing?
        // The original implementation exposed self::$routes implicitly via internal usage.
        // Depending on if other classes read it. But it was private. 
        // So we can remove the flat list or keep it if we want to support 'HEAD' logic easily for all.
        // Let's keep a flattened structure ONLY if strictly necessary, but for memory efficiency better not to duplicate too much.
        // However, the original code had: foreach (self::$routes as $route) logic.
        // We are replacing that completely.
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

        // 1. Check Static Routes
        if (isset(self::$staticRoutes[$method][$uri])) {
            $handler = self::$staticRoutes[$method][$uri];
            $route = ['method' => $method, 'path' => $uri, 'handler' => $handler];

            Hook::run('router_matched', $route, []);
            call_user_func($handler);
            Hook::run('router_dispatch_after', $route, []);
            return true;
        }

        // 1.5 Handle HEAD requests for GET routes in static map
        if ($method === 'HEAD' && isset(self::$staticRoutes['GET'][$uri])) {
            $handler = self::$staticRoutes['GET'][$uri];
            $route = ['method' => 'GET', 'path' => $uri, 'handler' => $handler];

            Hook::run('router_matched', $route, []);
            call_user_func($handler);
            Hook::run('router_dispatch_after', $route, []);
            return true;
        }

        // 2. Check Dynamic Routes
        $methodsToCheck = [$method];
        if ($method === 'HEAD') {
            $methodsToCheck[] = 'GET';
        }

        foreach ($methodsToCheck as $checkMethod) {
            if (!empty(self::$dynamicRoutes[$checkMethod])) {
                foreach (self::$dynamicRoutes[$checkMethod] as $route) {
                    if (preg_match($route['regex'], $uri, $matches)) {
                        array_shift($matches); // Remove full match

                        // Construct route array for hooks (mimicking old structure)
                        $routeData = [
                            'method' => $checkMethod,
                            'path' => $route['path'],
                            'handler' => $route['handler']
                        ];

                        Hook::run('router_matched', $routeData, $matches);
                        call_user_func_array($route['handler'], $matches);
                        Hook::run('router_dispatch_after', $routeData, $matches);
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public static function handle()
    {
        $uri = Request::path();
        $method = Request::server('REQUEST_METHOD', 'GET');

        $handled = self::dispatch($method, $uri);

        if (!$handled) {
            // Hook for 404
            Hook::run('router_404', $uri);

            if (strpos($uri, '/api/') === 0 || strpos($uri, '/@/') === 0) {
                Response::json(['error' => 'API Endpoint Not Found'], 404);
            } else {
                echo "File not found";
            }
        }
    }
}
