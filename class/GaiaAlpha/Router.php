<?php

namespace GaiaAlpha;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(string $method, string $uri)
    {
        // Simple exact match or regex
        foreach ($this->routes as $route) {
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
}
