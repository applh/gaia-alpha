<?php

namespace McpServer;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Model\Page;
use GaiaAlpha\SiteManager;
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
                return [
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
                        ]
                    ]
                ];

            case 'prompts/list':
                return [
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
                        ]
                    ]
                ];

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
                        ]
                    ]
                ];

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

        switch ($name) {
            case 'system_info':
                return $this->resultText('Gaia Alpha v' . Env::get('version') . ' (PHP ' . phpversion() . ')');

            case 'list_sites':
                $sites = SiteManager::getAllSites();
                return $this->resultJson($sites);

            case 'create_site':
                $domain = $arguments['domain'];
                // Logic derived from SiteCommands::handleCreate
                $rootDir = Env::get('root_dir');
                $sitePath = $rootDir . '/my-data/sites/' . $domain;
                if (File::isDirectory($sitePath)) {
                    throw new \Exception("Site directory '$domain' already exists.");
                }
                File::makeDirectory($sitePath);
                File::makeDirectory($sitePath . '/assets');
                $dbPath = $sitePath . '/database.sqlite';
                $dsn = 'sqlite:' . $dbPath;
                $db = new Database($dsn);
                $db->ensureSchema();

                // Switch to new site to bootstrap it
                $this->switchSite($domain);
                $userId = \GaiaAlpha\Model\User::create('admin', 'admin', 100);
                Page::create($userId, [
                    'title' => 'Home',
                    'slug' => 'home',
                    'content' => '<h1>Welcome to ' . $domain . '</h1>',
                    'cat' => 'page'
                ]);

                return $this->resultText("Site '$domain' created successfully with admin/admin credentials.");

            case 'list_pages':
                $pages = DB::fetchAll("SELECT id, title, slug, cat, created_at FROM cms_pages WHERE cat = 'page' ORDER BY created_at DESC");
                return $this->resultJson($pages);

            case 'get_page':
                $slug = $arguments['slug'];
                $page = Page::findBySlug($slug);
                if (!$page) {
                    throw new \Exception("Page not found: $slug");
                }
                return $this->resultJson($page);

            case 'upsert_page':
                $slug = $arguments['slug'];
                $existing = Page::findBySlug($slug);
                // We use first user (admin) for MCP operations by default for now
                $userId = 1;
                if ($existing) {
                    Page::update($existing['id'], $userId, $arguments);
                    return $this->resultText("Page '$slug' updated.");
                } else {
                    Page::create($userId, $arguments);
                    return $this->resultText("Page '$slug' created.");
                }

            case 'db_query':
                $sql = $arguments['sql'];
                if (stripos(trim($sql), 'SELECT') !== 0) {
                    throw new \Exception("Only SELECT queries are allowed via this tool.");
                }
                $results = DB::fetchAll($sql);
                return $this->resultJson($results);

            case 'list_media':
                $rootDir = Env::get('root_dir');
                $assetsDir = ($site === 'default') ? $rootDir . '/my-data/assets' : $rootDir . '/my-data/sites/' . $site . '/assets';
                if (!File::isDirectory($assetsDir)) {
                    return $this->resultText("No assets directory found for site '$site'.");
                }
                $files = File::glob($assetsDir . '/*');
                $result = [];
                foreach ($files as $file) {
                    $result[] = [
                        'name' => basename($file),
                        'size' => filesize($file),
                        'mtime' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
                return $this->resultJson($result);

            case 'read_log':
                $lines = $arguments['lines'] ?? 50;
                $logFile = Env::get('root_dir') . '/my-data/logs/system.log';
                if (!File::exists($logFile)) {
                    return $this->resultText("Log file not found at $logFile");
                }
                // Implementation of tail -n
                $fileContent = file($logFile);
                $lastLines = array_slice($fileContent, -$lines);
                return $this->resultText(implode("", $lastLines));

            case 'verify_system_health':
                $rootDir = Env::get('root_dir');
                $health = [
                    'version' => Env::get('version'),
                    'php_version' => phpversion(),
                    'directories' => [
                        'my-data' => is_writable($rootDir . '/my-data'),
                        'my-data/sites' => is_writable($rootDir . '/my-data/sites'),
                        'my-data/logs' => is_writable($rootDir . '/my-data/logs'),
                    ],
                    'database' => [
                        'connected' => true,
                        'site' => $this->currentSiteDomain
                    ]
                ];
                return $this->resultJson($health);

            case 'backup_site':
                $rootDir = Env::get('root_dir');
                $backupDir = $rootDir . '/my-data/backups';
                if (!File::isDirectory($backupDir)) {
                    File::makeDirectory($backupDir);
                }
                $siteDir = ($site === 'default') ? $rootDir . '/my-data' : $rootDir . '/my-data/sites/' . $site;
                $zipFile = $backupDir . '/' . ($site === 'default' ? 'default' : $site) . '_' . date('Ymd_His') . '.zip';

                // We use a simple system call to zip for speed in this tool
                // In a real app we'd use ZipArchive
                if ($site === 'default') {
                    // For default, we only backup the default database and assets
                    $cmd = "cd " . escapeshellarg($rootDir . '/my-data') . " && zip -r " . escapeshellarg($zipFile) . " database.sqlite assets/";
                } else {
                    $cmd = "cd " . escapeshellarg($rootDir . '/my-data/sites') . " && zip -r " . escapeshellarg($zipFile) . " " . escapeshellarg($site);
                }

                $output = [];
                $return = 0;
                exec($cmd, $output, $return);

                if ($return !== 0) {
                    throw new \Exception("Backup failed: " . implode("\n", $output));
                }

                return $this->resultText("Backup created at: " . str_replace($rootDir . '/', '', $zipFile));

            case 'install_plugin':
                $name = $arguments['name'];
                $pluginDir = Env::get('root_dir') . '/plugins/' . $name;
                if (File::isDirectory($pluginDir)) {
                    return $this->resultText("Plugin '$name' is already installed.");
                }
                // Simulated installation: create a basic plugin structure
                File::makeDirectory($pluginDir);
                File::write($pluginDir . '/plugin.json', json_encode([
                    'name' => $name,
                    'version' => '1.0.0',
                    'description' => "Installed via MCP",
                    'type' => 'user'
                ], JSON_PRETTY_PRINT));
                File::write($pluginDir . '/index.php', "<?php\n\n// Plugin $name initialized\n");

                return $this->resultText("Plugin '$name' installed successfully (simulated).");

            case 'analyze_seo':
                $slug = $arguments['slug'];
                $keyword = $arguments['keyword'] ?? null;
                $page = Page::findBySlug($slug);
                if (!$page) {
                    throw new \Exception("Page not found: $slug");
                }

                $content = $page['content'] ?? '';
                $title = $page['title'] ?? '';
                $metaDesc = $page['meta_description'] ?? '';

                $report = [
                    'page' => $slug,
                    'score' => 0,
                    'checks' => [],
                    'suggestions' => []
                ];

                $score = 0;
                $totalChecks = 0;

                // Title check
                $totalChecks++;
                $titleLen = mb_strlen($title);
                if ($titleLen >= 50 && $titleLen <= 60) {
                    $score += 10;
                    $report['checks'][] = "Title length is ideal ($titleLen chars).";
                } else {
                    $report['suggestions'][] = "Title length ($titleLen) should be between 50-60 characters.";
                }

                // Meta Description check
                $totalChecks++;
                if (!empty($metaDesc)) {
                    $metaLen = mb_strlen($metaDesc);
                    if ($metaLen >= 150 && $metaLen <= 160) {
                        $score += 20;
                        $report['checks'][] = "Meta description length is ideal ($metaLen chars).";
                    } else {
                        $report['suggestions'][] = "Meta description length ($metaLen) should be between 150-160 characters.";
                    }
                } else {
                    $report['suggestions'][] = "Meta description is missing.";
                }

                // H1 check
                $totalChecks++;
                preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $content, $h1s);
                $h1Count = count($h1s[0]);
                if ($h1Count === 1) {
                    $score += 20;
                    $report['checks'][] = "Exactly one H1 tag found.";
                } elseif ($h1Count === 0) {
                    $report['suggestions'][] = "H1 tag is missing.";
                } else {
                    $report['suggestions'][] = "Multiple H1 tags found ($h1Count). There should be exactly one.";
                }

                // H2 check
                $totalChecks++;
                if (preg_match('/<h2[^>]*>/i', $content)) {
                    $score += 10;
                    $report['checks'][] = "H2 tags are present.";
                } else {
                    $report['suggestions'][] = "Consider adding H2 tags for better structure.";
                }

                // Image Alt check
                preg_match_all('/<img[^>]+>/i', $content, $imgs);
                if (count($imgs[0]) > 0) {
                    $totalChecks++;
                    $missingAlt = 0;
                    foreach ($imgs[0] as $img) {
                        if (stripos($img, 'alt=') === false || preg_match('/alt=["\']\s*["\']/i', $img)) {
                            $missingAlt++;
                        }
                    }
                    if ($missingAlt === 0) {
                        $score += 20;
                        $report['checks'][] = "All images have alt tags.";
                    } else {
                        $report['suggestions'][] = "$missingAlt image(s) are missing descriptive alt tags.";
                    }
                }

                // Keyword Density check
                if ($keyword) {
                    $totalChecks++;
                    $strippedContent = strip_tags($content);
                    $keywordCount = mb_substr_count(mb_strtolower($strippedContent), mb_strtolower($keyword));
                    $wordCount = str_word_count($strippedContent);
                    $density = ($wordCount > 0) ? ($keywordCount / $wordCount) * 100 : 0;

                    if ($density >= 1 && $density <= 3) {
                        $score += 20;
                        $report['checks'][] = "Keyword density is ideal (" . round($density, 2) . "%).";
                    } elseif ($density > 3) {
                        $report['suggestions'][] = "Keyword density is high (" . round($density, 2) . "%). Avoid keyword stuffing.";
                    } else {
                        $report['suggestions'][] = "Keyword '$keyword' not found or density too low (" . round($density, 2) . "%).";
                    }
                }

                $report['score'] = round($score); // Simplified scoring for now
                return $this->resultJson($report);

            default:
                throw new \Exception("Tool not found: $name", -32601);
        }
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
