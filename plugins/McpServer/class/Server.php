<?php

namespace McpServer;

class Server
{
    private $input;
    private $output;

    public function __construct()
    {
        $this->input = fopen('php://stdin', 'r');
        $this->output = fopen('php://stdout', 'w');
    }

    public function runStdio()
    {
        while (!feof($this->input)) {
            $line = fgets($this->input);
            if ($line === false)
                break;

            $line = trim($line);
            if (empty($line))
                continue;

            $request = json_decode($line, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Log error to stderr?
                fwrite(STDERR, "Invalid JSON: " . json_last_error_msg() . "\n");
                continue;
            }

            $response = $this->handleRequest($request);
            if ($response) {
                fwrite($this->output, json_encode($response) . "\n");
                fflush($this->output);
            }
        }
    }

    private function handleRequest($request)
    {
        if (!isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0') {
            return null;
        }

        $id = $request['id'] ?? null;
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];

        try {
            $result = $this->dispatch($method, $params);

            // Notification (no ID) -> no response
            if ($id === null) {
                return null;
            }

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $result
            ];
        } catch (\Exception $e) {
            if ($id === null)
                return null;
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => $e->getCode() ?: -32603,
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    private function dispatch($method, $params)
    {
        switch ($method) {
            case 'initialize':
                return [
                    'protocolVersion' => '2024-11-05', // Use latest protocol version or match spec
                    'capabilities' => [
                        'tools' => [
                            'listChanged' => false
                        ],
                        'resources' => [
                            'listChanged' => false
                        ]
                    ],
                    'serverInfo' => [
                        'name' => 'GaiaAlpha MCP',
                        'version' => '1.0.0'
                    ]
                ];

            case 'initialized':
                // Client acknowledging initialization
                return [];

            case 'tools/list':
                return [
                    'tools' => [
                        [
                            'name' => 'system_info',
                            'description' => 'Get system version and status',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => new \stdClass()
                            ]
                        ]
                    ]
                ];

            case 'tools/call':
                $name = $params['name'] ?? '';
                if ($name === 'system_info') {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Gaia Alpha v' . \GaiaAlpha\Env::get('version') . ' (PHP ' . phpversion() . ')'
                            ]
                        ]
                    ];
                }
                throw new \Exception("Tool not found: $name", -32601);

            case 'ping': // Custom ping
                return "pong";

            default:
                throw new \Exception("Method not found: $method", -32601);
        }
    }
}
