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

class Server
{
    private $input;
    private $output;
    private $currentSiteDomain = 'default';

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
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => [
                        'tools' => ['listChanged' => false],
                        'resources' => ['listChanged' => false]
                    ],
                    'serverInfo' => [
                        'name' => 'GaiaAlpha MCP',
                        'version' => '1.1.0'
                    ]
                ];

            case 'initialized':
                return [];

            case 'tools/list':
                $result = [
                    'tools' => [
                        [
                            'name' => 'system_info',
                            'description' => 'Get system version and status',
                            'inputSchema' => ['type' => 'object', 'properties' => (object) []]
                        ],
                        [
                            'name' => 'list_sites',
                            'description' => 'List all managed sites',
                            'inputSchema' => ['type' => 'object', 'properties' => (object) []]
                        ],
                        [
                            'name' => 'create_site',
                            'description' => 'Create a new site with a domain name',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'domain' => ['type' => 'string', 'description' => 'The domain name (e.g. example.com)']
                                ],
                                'required' => ['domain']
                            ]
                        ],
                        [
                            'name' => 'list_pages',
                            'description' => 'List all pages for a specific site',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                                ]
                            ]
                        ],
                        [
                            'name' => 'get_page',
                            'description' => 'Get full content of a page by its slug',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'slug' => ['type' => 'string', 'description' => 'Page slug'],
                                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                                ],
                                'required' => ['slug']
                            ]
                        ],
                        [
                            'name' => 'upsert_page',
                            'description' => 'Create or update a page',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'slug' => ['type' => 'string'],
                                    'content' => ['type' => 'string'],
                                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                                ],
                                'required' => ['title', 'slug', 'content']
                            ]
                        ],
                        [
                            'name' => 'db_query',
                            'description' => 'Execute a read-only SQL query on the selected site database',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'sql' => ['type' => 'string', 'description' => 'SQL query (must be a SELECT statement)'],
                                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                                ],
                                'required' => ['sql']
                            ]
                        ],
                        [
                            'name' => 'list_media',
                            'description' => 'List all media files for a specific site',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                                ]
                            ]
                        ],
                        [
                            'name' => 'read_log',
                            'description' => 'Read system logs',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'lines' => ['type' => 'integer', 'description' => 'Number of last lines to read', 'default' => 50]
                                ]
                            ]
                        ],
                        [
                            'name' => 'verify_system_health',
                            'description' => 'Check system health and directory permissions',
                            'inputSchema' => ['type' => 'object', 'properties' => (object) []]
                        ],
                        [
                            'name' => 'backup_site',
                            'description' => 'Create a backup of a site including database and assets',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                                ]
                            ]
                        ],
                        [
                            'name' => 'install_plugin',
                            'description' => 'Install a new plugin (simulated)',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string', 'description' => 'Plugin name']
                                ],
                                'required' => ['name']
                            ]
                        ],
                        [
                            'name' => 'analyze_seo',
                            'description' => 'Analyze SEO for a specific page',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'slug' => ['type' => 'string', 'description' => 'Page slug'],
                                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)'],
                                    'keyword' => ['type' => 'string', 'description' => 'Target keyword (optional)']
                                ],
                                'required' => ['slug']
                            ]
                        ],
                        [
                            'name' => 'list_users',
                            'description' => 'List all users for a specific site',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                                ]
                            ]
                        ],
                        [
                            'name' => 'create_user',
                            'description' => 'Create a new user',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'username' => ['type' => 'string'],
                                    'password' => ['type' => 'string'],
                                    'level' => ['type' => 'integer', 'description' => 'Access level (10=member, 100=admin)'],
                                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                                ],
                                'required' => ['username', 'password']
                            ]
                        ],
                        [
                            'name' => 'update_user_permissions',
                            'description' => 'Update user permissions or password',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'user_id' => ['type' => 'integer'],
                                    'level' => ['type' => 'integer'],
                                    'password' => ['type' => 'string'],
                                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                                ],
                                'required' => ['user_id']
                            ]
                        ]
                    ]
                ];
                return Hook::filter('mcp_tools', $result);

            case 'prompts/list':
                $result = [
                    'prompts' => [
                        [
                            'name' => 'summarize_page',
                            'description' => 'Summarize the content of a page',
                            'arguments' => [
                                [
                                    'name' => 'slug',
                                    'description' => 'Slug of the page to summarize',
                                    'required' => true
                                ]
                            ]
                        ],
                        [
                            'name' => 'summarize_health',
                            'description' => 'Check system health and summarize findings',
                            'arguments' => []
                        ]
                    ]
                ];
                return Hook::filter('mcp_prompts', $result);

            case 'prompts/get':
                $name = $params['name'];
                $args = $params['arguments'] ?? [];
                if ($name === 'summarize_page') {
                    $slug = $args['slug'] ?? 'home';
                    return [
                        'description' => 'Summarize the content of a page',
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    'type' => 'text',
                                    'text' => "Please summarize the content of the page with slug '$slug'. You can use the 'get_page' tool to retrieve the content first."
                                ]
                            ]
                        ]
                    ];
                }
                if ($name === 'summarize_health') {
                    return [
                        'description' => 'Check system health and summarize findings',
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    'type' => 'text',
                                    'text' => "Please run the 'verify_system_health' and 'read_log' tools to check the current system state, and then provide a concise summary of the health status and any active errors."
                                ]
                            ]
                        ]
                    ];
                }
                throw new \Exception("Prompt not found: $name", -32602);

            case 'tools/call':
                return $this->handleToolCall($params['name'], $params['arguments'] ?? []);

            case 'resources/list':
                return [
                    'resources' => [
                        [
                            'uri' => 'cms://sites/list',
                            'name' => 'All Sites',
                            'mimeType' => 'application/json'
                        ],
                        [
                            'uri' => 'cms://system/logs',
                            'name' => 'System Logs',
                            'mimeType' => 'text/plain'
                        ],
                        [
                            'uri' => 'cms://sites/{site}/database/tables',
                            'name' => 'Site Database Tables',
                            'mimeType' => 'application/json'
                        ],
                        [
                            'uri' => 'cms://sites/packages',
                            'name' => 'Site Packages',
                            'mimeType' => 'application/json'
                        ]
                    ]
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
        if ($uri === 'cms://sites/list') {
            $sites = SiteManager::getAllSites();
            return [
                'contents' => [
                    [
                        'uri' => $uri,
                        'mimeType' => 'application/json',
                        'text' => json_encode($sites, JSON_PRETTY_PRINT)
                    ]
                ]
            ];
        }

        if ($uri === 'cms://system/logs') {
            $logFile = Env::get('root_dir') . '/my-data/logs/system.log';
            if (!File::exists($logFile)) {
                return [
                    'contents' => [
                        [
                            'uri' => $uri,
                            'mimeType' => 'text/plain',
                            'text' => 'Log file not found.'
                        ]
                    ]
                ];
            }
            return [
                'contents' => [
                    [
                        'uri' => $uri,
                        'mimeType' => 'text/plain',
                        'text' => File::read($logFile)
                    ]
                ]
            ];
        }

        if ($uri === 'cms://sites/packages') {
            $packagesDir = Env::get('root_dir') . '/docs/examples';
            $packages = [];
            if (File::isDirectory($packagesDir)) {
                $dirs = File::glob($packagesDir . '/*', GLOB_ONLYDIR);
                foreach ($dirs as $dir) {
                    $packages[] = [
                        'name' => basename($dir),
                        'path' => str_replace(Env::get('root_dir') . '/', '', $dir)
                    ];
                }
            }
            return [
                'contents' => [
                    [
                        'uri' => $uri,
                        'mimeType' => 'application/json',
                        'text' => json_encode($packages, JSON_PRETTY_PRINT)
                    ]
                ]
            ];
        }

        if (preg_match('#^cms://sites/([^/]+)/database/tables$#', $uri, $matches)) {
            $site = $matches[1];
            $this->switchSite($site);
            $tables = DB::getTables();
            $result = [];
            foreach ($tables as $table) {
                $result[] = [
                    'name' => $table,
                    'schema' => DB::getTableSchema($table),
                    'count' => DB::getTableCount($table)
                ];
            }
            return [
                'contents' => [
                    [
                        'uri' => $uri,
                        'mimeType' => 'application/json',
                        'text' => json_encode($result, JSON_PRETTY_PRINT)
                    ]
                ]
            ];
        }

        // Check if other plugins want to handle this resource
        $hookResult = Hook::filter('mcp_resource_read', null, $uri);
        if ($hookResult !== null) {
            return $hookResult;
        }

        // Potential for more resources like cms://sites/{domain}/pages
        throw new \Exception("Resource not found: $uri", -32602);
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
