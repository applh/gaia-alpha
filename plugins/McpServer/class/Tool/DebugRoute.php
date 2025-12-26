<?php

namespace McpServer\Tool;

use GaiaAlpha\Router;

class DebugRoute extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'debug_route',
            'description' => 'Test a URL path against the router to see what controller matched',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'The URI path to test (e.g. /@/console/run)'
                    ],
                    'method' => [
                        'type' => 'string',
                        'default' => 'GET',
                        'description' => 'HTTP Method (GET, POST, etc)'
                    ]
                ],
                'required' => ['path']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $path = $arguments['path'];
        $method = strtoupper($arguments['method'] ?? 'GET');

        $match = Router::matchRoute($method, $path);

        if ($match) {
            $response = [
                'status' => 'matched',
                'match_details' => [
                    'type' => $match['type'],
                    'method' => $match['method'],
                    'path' => $match['path'],
                    'handler' => $this->formatHandler($match['handler']),
                    'params' => $match['params']
                ]
            ];

            if (isset($match['regex'])) {
                $response['match_details']['regex'] = $match['regex'];
            }

            return $this->resultJson($response);

        } else {
            return $this->resultJson([
                'status' => 'no_match',
                'message' => "No route matched for $method $path"
            ]);
        }
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
