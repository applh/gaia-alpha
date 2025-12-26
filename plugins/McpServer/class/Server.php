<?php

namespace McpServer;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Model\Page;
use GaiaAlpha\Model\User;
use GaiaAlpha\SiteManager;
use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use GaiaAlpha\Database;
use GaiaAlpha\File;
use McpServer\Service\McpLogger;

class Server
{
    private $input;
    private $output;
    private $currentSiteDomain = 'default';
    private $sessionId = null;
    private $clientInfo = [];

    public function __construct($input = null, $output = null)
    {
        $this->input = $input ?: fopen('php://stdin', 'r');
        $this->output = $output ?: fopen('php://stdout', 'w');
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
                $this->log("Invalid JSON: " . json_last_error_msg());
                continue;
            }

            $response = $this->handleRequest($request);
            if ($response) {
                fwrite($this->output, json_encode($response) . "\n");
                fflush($this->output);
            }
        }
    }

    private function log($message)
    {
        fwrite(STDERR, "[MCP] " . $message . "\n");
    }

    /**
     * Handle a JSON-RPC request
     * Made public to support SSE transport
     * @param array $request JSON-RPC request
     * @return array|null JSON-RPC response
     */
    public function handleRequestPublic($request, $sessionId = null)
    {
        $this->sessionId = $sessionId;
        return $this->handleRequest($request);
    }

    private function handleRequest($request)
    {
        if (!isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0') {
            return null;
        }

        $id = $request['id'] ?? null;
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];

        $startTime = microtime(true);
        $response = null;

        try {
            $result = $this->dispatch($method, $params);

            if ($id === null) {
                // This is a notification, do not return a response
                $response = null;
            } else {
                $response = [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => $result
                ];
            }
        } catch (\Exception $e) {
            if ($id === null) {
                // This is a notification, do not return an error response
                $response = null;
            } else {
                $response = [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => $e->getCode() ?: -32603,
                        'message' => $e->getMessage()
                    ]
                ];
            }
        }

        $duration = microtime(true) - $startTime;

        // Use hook for pluggable logging
        Hook::run('mcp_request_handled', [
            'request' => $request,
            'response' => $response,
            'duration' => $duration,
            'sessionId' => $this->sessionId,
            'siteDomain' => $this->currentSiteDomain,
            'clientInfo' => $this->clientInfo
        ]);

        return $response;
    }

    private function dispatch($method, $params)
    {
        switch ($method) {
            case 'initialize':
                if (isset($params['clientInfo'])) {
                    $this->clientInfo = $params['clientInfo'];
                }
                $protocolVersion = $params['protocolVersion'] ?? '2024-11-05';
                return [
                    'protocolVersion' => $protocolVersion,
                    'capabilities' => [
                        'tools' => ['listChanged' => false],
                        'resources' => ['listChanged' => false],
                        'prompts' => ['listChanged' => false]
                    ],
                    'serverInfo' => [
                        'name' => 'GaiaAlpha MCP',
                        'version' => '1.1.1'
                    ]
                ];

            case 'initialized':
            case 'notifications/initialized':
                return [];

            case 'tools/list':
                $result = [
                    'tools' => $this->getRegisteredTools()
                ];
                return Hook::filter('mcp_tools', $result);

            case 'prompts/list':
                $result = [
                    'prompts' => $this->getRegisteredPrompts()
                ];
                return Hook::filter('mcp_prompts', $result);

            case 'prompts/get':
                $name = $params['name'];
                $args = $params['arguments'] ?? [];

                $className = 'McpServer\\Prompt\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
                if (class_exists($className)) {
                    $prompt = new $className();
                    return $prompt->getPrompt($args);
                }
                throw new \Exception("Prompt not found: $name", -32602);

            case 'tools/call':
                return $this->handleToolCall($params['name'], $params['arguments'] ?? []);

            case 'resources/list':
                $result = [
                    'resources' => $this->getRegisteredResources()
                ];
                return Hook::filter('mcp_resources', $result);

            case 'resources/read':
                return $this->handleResourceRead($params['uri']);

            case 'ping':
                return "pong";

            default:
                throw new \Exception("Method not found: $method", -32601);
        }
    }

    private function handleToolCall($name, $arguments)
    {
        $site = $arguments['site'] ?? 'default';
        $this->switchSite($site);

        // Check if other plugins want to handle this tool call
        $hookResult = Hook::filter('mcp_tool_call', null, $name, $arguments);
        if ($hookResult !== null) {
            return $hookResult;
        }

        // Dynamic Tool Loading
        $className = 'McpServer\\Tool\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));

        if (class_exists($className)) {
            $tool = new $className();
            return $tool->execute($arguments);
        }

        throw new \Exception("Tool not found: $name", -32601);
    }

    private function handleResourceRead($uri)
    {
        $resources = $this->getRegisteredResources(true);

        foreach ($resources as $resource) {
            $matches = $resource->matches($uri);
            if ($matches !== null) {
                // Auto-switch site if 'site' or first match is found (convention)
                $site = $matches['site'] ?? $matches[1] ?? null;
                if ($site && strpos($uri, "cms://sites/$site") !== false) {
                    $this->switchSite($site);
                }

                return $resource->read($uri, $matches);
            }
        }

        // Check if other plugins want to handle this resource
        $hookResult = Hook::filter('mcp_resource_read', null, $uri);
        if ($hookResult !== null) {
            return $hookResult;
        }

        throw new \Exception("Resource not found: $uri", -32602);
    }

    private function getRegisteredResources($instantiated = false)
    {
        $resources = [];
        $dir = __DIR__ . '/Resource';
        if (!is_dir($dir))
            return [];

        $files = glob($dir . '/*.php');

        foreach ($files as $file) {
            $baseName = basename($file, '.php');
            if ($baseName === 'BaseResource')
                continue;

            $className = 'McpServer\\Resource\\' . $baseName;
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                if (!$reflection->isAbstract()) {
                    $resource = new $className();
                    $resources[] = $instantiated ? $resource : $resource->getDefinition();
                }
            }
        }

        return $resources;
    }

    private function getRegisteredPrompts()
    {
        $prompts = [];
        $dir = __DIR__ . '/Prompt';
        if (!is_dir($dir))
            return [];

        $files = glob($dir . '/*.php');

        foreach ($files as $file) {
            $baseName = basename($file, '.php');
            if ($baseName === 'BasePrompt')
                continue;

            $className = 'McpServer\\Prompt\\' . $baseName;
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                if (!$reflection->isAbstract()) {
                    $prompt = new $className();
                    $prompts[] = $prompt->getDefinition();
                }
            }
        }

        return $prompts;
    }

    private function getRegisteredTools()
    {
        $tools = [];
        $dir = __DIR__ . '/Tool';
        $files = glob($dir . '/*.php');

        foreach ($files as $file) {
            $baseName = basename($file, '.php');
            if ($baseName === 'BaseTool')
                continue;

            $className = 'McpServer\\Tool\\' . $baseName;
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                if (!$reflection->isAbstract()) {
                    $tool = new $className();
                    $tools[] = $tool->getDefinition();
                }
            }
        }

        return $tools;
    }

    private function switchSite($domain)
    {
        if ($this->currentSiteDomain === $domain) {
            return;
        }

        $rootDir = Env::get('root_dir');
        if ($domain === 'default') {
            $dbPath = $rootDir . '/my-data/database.sqlite';
        } else {
            $dbPath = $rootDir . '/my-data/sites/' . $domain . '/database.sqlite';
        }

        if (!File::exists($dbPath)) {
            throw new \Exception("Site database not found for domain: $domain");
        }

        $dsn = 'sqlite:' . $dbPath;
        $db = new Database($dsn);
        DB::setConnection($db);
        $this->currentSiteDomain = $domain;
    }

    private function resultText($text)
    {
        return [
            'content' => [
                ['type' => 'text', 'text' => $text]
            ]
        ];
    }

    private function resultJson($data)
    {
        return $this->resultText(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
