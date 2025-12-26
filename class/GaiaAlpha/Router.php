<?php

namespace GaiaAlpha;

use GaiaAlpha\Env;
use GaiaAlpha\Request;

class Router
{
    private static array $staticRoutes = [];
    private static array $dynamicRoutes = [];
    private static array $globalMiddleware = [];
    private static bool $isBooted = false;

    public static function pushMiddleware($middleware)
    {
        self::$globalMiddleware[] = $middleware;
    }

    public static function add(string $method, string $path, callable $handler)
    {
        $method = strtoupper($method);
        if (preg_match('/[\[\(\*\?\+]/', $path)) {
            self::$dynamicRoutes[$method][] = [
                'regex' => '#^' . $path . '$#',
                'handler' => $handler,
                'path' => $path
            ];
        } else {
            self::$staticRoutes[$method][$path] = $handler;
        }
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

    public static function adminPrefix(): string
    {
        $prefixes = Env::get('admin_prefixes', ['/@/admin']);
        return is_array($prefixes) ? $prefixes[0] : $prefixes;
    }

    public static function allDashPrefixes(): array
    {
        $admin = (array) Env::get('admin_prefixes', ['/@/admin']);
        $app = (array) Env::get('app_prefixes', ['/@/app']);
        return array_unique(array_merge($admin, $app));
    }

    public static function getRoutes(): array
    {
        return [
            'static' => self::$staticRoutes,
            'dynamic' => self::$dynamicRoutes
        ];
    }

    public static function matchRoute(string $method, string $uri): ?array
    {
        // 1. Check Static Routes
        if (isset(self::$staticRoutes[$method][$uri])) {
            return [
                'type' => 'static',
                'method' => $method,
                'path' => $uri,
                'handler' => self::$staticRoutes[$method][$uri],
                'params' => []
            ];
        }

        // 1.5 Handle HEAD requests for GET routes in static map
        if ($method === 'HEAD' && isset(self::$staticRoutes['GET'][$uri])) {
            return [
                'type' => 'static',
                'method' => 'GET',
                'path' => $uri,
                'handler' => self::$staticRoutes['GET'][$uri],
                'params' => []
            ];
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
                        return [
                            'type' => 'dynamic',
                            'method' => $checkMethod,
                            'path' => $route['path'],
                            'regex' => $route['regex'],
                            'handler' => $route['handler'],
                            'params' => $matches
                        ];
                    }
                }
            }
        }

        return null;
    }

    public static function dispatch(string $method, string $uri)
    {
        // Hook before dispatch
        Hook::run('router_dispatch_before', $method, $uri);

        $match = self::matchRoute($method, $uri);

        if ($match) {
            $routeData = [
                'method' => $match['method'],
                'path' => $match['path'],
                'handler' => $match['handler']
            ];

            Hook::run('router_matched', $routeData, $match['params']);

            $handler = $match['handler'];
            if (is_array($handler) && is_string($handler[0])) {
                // Instantiate on demand if it's a class string
                $className = $handler[0];
                $handler[0] = new $className();
            }

            if ($match['type'] === 'dynamic') {
                call_user_func_array($handler, $match['params']);
            } else {
                call_user_func($handler);
            }

            Hook::run('router_dispatch_after', $routeData, $match['params']);
            return true;
        }

        return false;
    }

    public static function handle()
    {
        $uri = Request::path();
        $method = Request::server('REQUEST_METHOD', 'GET');

        $pipeline = new Pipeline();
        $pipeline->send(self::$globalMiddleware)->then(function () use ($method, $uri) {
            $handled = self::dispatch($method, $uri);

            if (!$handled) {
                $context = Request::context();
                Hook::run('router_404', $uri);

                if ($context === 'api' || $context === 'admin') {
                    Response::json(['error' => 'Endpoint Not Found'], 404);
                } else {
                    echo "File not found";
                }
            }
        });
    }
}
