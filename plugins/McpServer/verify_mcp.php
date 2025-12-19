<?php

// Bootstrap Gaia Alpha
require_once __DIR__ . '/../../class/GaiaAlpha/App.php';
\GaiaAlpha\App::registerAutoloaders();
\GaiaAlpha\App::cli_setup(realpath(__DIR__ . '/../../'));

use McpServer\Server;
use GaiaAlpha\Env;

// Minimal Env setup
Env::set('root_dir', realpath(__DIR__ . '/../../'));
Env::set('path_data', Env::get('root_dir') . '/my-data');
Env::set('version', '1.0.0-test');

function testToolsList($server)
{
    echo "Testing tools/list\n";
    $request = [
        'jsonrpc' => '2.0',
        'id' => uniqid(),
        'method' => 'tools/list'
    ];

    $tmpIn = fopen('php://memory', 'r+');
    fwrite($tmpIn, json_encode($request) . "\n");
    rewind($tmpIn);

    $tmpOut = fopen('php://memory', 'r+');

    $s = new Server($tmpIn, $tmpOut);
    $s->runStdio();

    rewind($tmpOut);
    $response = stream_get_contents($tmpOut);
    echo "Response: " . $response . "\n\n";
    $data = json_decode($response, true);
    $toolsCount = count($data['result']['tools'] ?? []);
    echo "Total tools registered: $toolsCount\n";
    return $data;
}

function testTool($server, $name, $args = [])
{
    echo "Testing tool: $name\n";
    $request = [
        'jsonrpc' => '2.0',
        'id' => uniqid(),
        'method' => 'tools/call',
        'params' => [
            'name' => $name,
            'arguments' => $args
        ]
    ];

    // We can't easily use runStdio here because it's a loop.
    // Let's use a reflection or make handleRequest public for testing.
    // Or just use a temporary file as stdin.

    $tmpIn = fopen('php://memory', 'r+');
    fwrite($tmpIn, json_encode($request) . "\n");
    rewind($tmpIn);

    $tmpOut = fopen('php://memory', 'r+');

    $s = new Server($tmpIn, $tmpOut);
    $s->runStdio();

    rewind($tmpOut);
    $response = stream_get_contents($tmpOut);
    echo "Response: " . $response . "\n\n";
    return json_decode($response, true);
}

function testResource($server, $uri)
{
    echo "Testing resource: $uri\n";
    $request = [
        'jsonrpc' => '2.0',
        'id' => uniqid(),
        'method' => 'resources/read',
        'params' => [
            'uri' => $uri
        ]
    ];

    $tmpIn = fopen('php://memory', 'r+');
    fwrite($tmpIn, json_encode($request) . "\n");
    rewind($tmpIn);

    $tmpOut = fopen('php://memory', 'r+');

    $s = new Server($tmpIn, $tmpOut);
    $s->runStdio();

    rewind($tmpOut);
    $response = stream_get_contents($tmpOut);
    echo "Response: " . $response . "\n\n";
    return json_decode($response, true);
}

function testPrompt($server, $name, $args = [])
{
    echo "Testing prompt: $name\n";
    $request = [
        'jsonrpc' => '2.0',
        'id' => uniqid(),
        'method' => 'prompts/get',
        'params' => [
            'name' => $name,
            'arguments' => $args
        ]
    ];

    $tmpIn = fopen('php://memory', 'r+');
    fwrite($tmpIn, json_encode($request) . "\n");
    rewind($tmpIn);

    $tmpOut = fopen('php://memory', 'r+');

    $s = new Server($tmpIn, $tmpOut);
    $s->runStdio();

    rewind($tmpOut);
    $response = stream_get_contents($tmpOut);
    echo "Response: " . $response . "\n\n";
    return json_decode($response, true);
}

$server = new Server();

// 0. Test dynamic tool discovery
testToolsList($server);

// 1. List Sites
testTool($server, 'list_sites');

// 2. System Info
testTool($server, 'system_info');

// 3. Create Site (Clean up if exists)
$testDomain = 'mcp-test.com';
$sitePath = Env::get('root_dir') . '/my-data/sites/' . $testDomain;
if (is_dir($sitePath)) {
    // Basic cleanup
    array_map('unlink', glob("$sitePath/*.*"));
    @rmdir("$sitePath/assets");
    @rmdir($sitePath);
}
testTool($server, 'create_site', ['domain' => $testDomain]);

// 4. List Pages on new site
testTool($server, 'list_pages', ['site' => $testDomain]);

// 5. Upsert Page on new site
testTool($server, 'upsert_page', [
    'site' => $testDomain,
    'title' => 'MCP Test Page',
    'slug' => 'mcp-test',
    'content' => 'Content from MCP'
]);

// 6. Get Page
testTool($server, 'get_page', [
    'site' => $testDomain,
    'slug' => 'mcp-test'
]);

// 7. DB Query
testTool($server, 'db_query', [
    'site' => $testDomain,
    'sql' => 'SELECT * FROM cms_pages WHERE slug = "mcp-test"'
]);

// 8. List Media
testTool($server, 'list_media', ['site' => $testDomain]);

// 9. Verify Health
testTool($server, 'verify_system_health');

// 10. Read Log
testTool($server, 'read_log', ['lines' => 10]);

// 11. Backup Site
testTool($server, 'backup_site', ['site' => $testDomain]);

// 12. Install Plugin
testTool($server, 'install_plugin', ['name' => 'TestPluginMcp']);

// 13. Resource: Database Tables
testResource($server, "cms://sites/$testDomain/database/tables");

// 14. Prompts List
echo "Testing prompts/list\n";
$req = ['jsonrpc' => '2.0', 'id' => 'p1', 'method' => 'prompts/list'];
$tmpIn = fopen('php://memory', 'r+');
fwrite($tmpIn, json_encode($req) . "\n");
rewind($tmpIn);
$tmpOut = fopen('php://memory', 'r+');
$s = new Server($tmpIn, $tmpOut);
$s->runStdio();
rewind($tmpOut);
echo "Response: " . stream_get_contents($tmpOut) . "\n\n";

// 15. Prompt Get
testPrompt($server, 'summarize_page', ['slug' => 'mcp-test']);

// 16. User Management
testTool($server, 'list_users', ['site' => $testDomain]);
testTool($server, 'create_user', [
    'site' => $testDomain,
    'username' => 'testuser',
    'password' => 'testpass',
    'level' => 10
]);
testTool($server, 'update_user_permissions', [
    'site' => $testDomain,
    'user_id' => 2, // Assuming first user is ID 1 (admin)
    'level' => 50
]);

// 17. Resources
testResource($server, "cms://system/logs");
testResource($server, "cms://sites/packages");
