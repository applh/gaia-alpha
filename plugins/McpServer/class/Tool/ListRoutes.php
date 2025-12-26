<?php

namespace McpServer\Tool;

use GaiaAlpha\Router;

class ListRoutes extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'list_routes',
            'description' => 'List all registered routes in the system',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'type' => [
                        'type' => 'string',
                        'enum' => ['all', 'static', 'dynamic'],
                        'description' => 'Filter by route type (default: all)'
                    ]
                ]
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $type = $arguments['type'] ?? 'all';
        $routes = Router::getRoutes();
        $output = [];

        if ($type === 'all' || $type === 'static') {
            foreach ($routes['static'] as $method => $paths) {
                foreach ($paths as $path => $handler) {
                    $output[] = [
                        'type' => 'static',
                        'method' => $method,
                        'path' => $path,
                        'handler' => $this->formatHandler($handler)
                    ];
                }
            }
        }

        if ($type === 'all' || $type === 'dynamic') {
            foreach ($routes['dynamic'] as $method => $items) {
                foreach ($items as $item) {
                    $output[] = [
                        'type' => 'dynamic',
                        'method' => $method,
                        'path' => $item['path'], // Original path pattern
                        'regex' => $item['regex'],
                        'handler' => $this->formatHandler($item['handler'])
                    ];
                }
            }
        }

        return $this->resultJson($output);
    }

    private function formatHandler($handler)
    {
        if (is_array($handler)) {
            $class = is_object($handler[0]) ? get_class($handler[0]) : $handler[0];
            return $class . '::' . $handler[1];
        } else if (is_string($handler)) {
            return $handler;
        } else if ($handler instanceof \Closure) {
            return 'Closure';
        } else {
            return 'Unknown';
        }
    }
}
