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

function testTool($name, $args = [])
{
    echo "--- Testing tool: $name ---\n";
    $request = [
        'jsonrpc' => '2.0',
        'id' => uniqid(),
        'method' => 'tools/call',
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

function testResource($uri)
{
    echo "--- Testing resource: $uri ---\n";
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
function testPrompt($name, $args = [])
{
    echo "--- Testing prompt: $name ---\n";
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

$testDomain = 'mcp-verify-new.test';

// 1. Setup - Create a site for testing new features
testTool('create_site', ['domain' => $testDomain]);

// 2. Test analyze_seo
testTool('upsert_page', [
    'site' => $testDomain,
    'title' => 'SEO Test Page',
    'slug' => 'seo-test',
    'content' => 'This is a test page for SEO. It contains some keywords like MCP and Gaia.'
]);
testTool('analyze_seo', [
    'site' => $testDomain,
    'slug' => 'seo-test',
    'keyword' => 'MCP'
]);

// 3. Test bulk_import_pages (JSON)
$importData = json_encode([
    ['title' => 'Imported Page 1', 'slug' => 'imp-1', 'content' => 'Content 1'],
    ['title' => 'Imported Page 2', 'slug' => 'imp-2', 'content' => 'Content 2']
]);
testTool('bulk_import_pages', [
    'site' => $testDomain,
    'format' => 'json',
    'data' => $importData
]);

// 4. Test ai_generate_image
testTool('ai_generate_image', [
    'site' => $testDomain,
    'prompt' => 'A futuristic CMS logo',
    'filename' => 'mcp-logo.png'
]);

// 5. Test search_plugins
testTool('search_plugins', ['query' => 'Mcp']);

// 6. Test generate_template_schema
testTool('generate_template_schema', [
    'description' => 'A landing page for a coffee shop with sections for menu, about, and location.'
]);

// 8. Test db_migration_assistant
testTool('db_migration_assistant', [
    'table' => 'cms_pages',
    'description' => 'Add a category column',
    'apply' => false
]);

// 9. Test New Prompts
testTool('list_sites'); // Just to ensure server is fresh
echo "Testing prompts/list\n";
$req = ['jsonrpc' => '2.0', 'id' => 'p2', 'method' => 'prompts/list'];
$tmpIn = fopen('php://memory', 'r+');
fwrite($tmpIn, json_encode($req) . "\n");
rewind($tmpIn);
$tmpOut = fopen('php://memory', 'r+');
$s = new Server($tmpIn, $tmpOut);
$s->runStdio();
rewind($tmpOut);
echo "Response: " . stream_get_contents($tmpOut) . "\n\n";

testPrompt('seo_specialist', ['slug' => 'seo-test']);
testPrompt('security_auditor');
testPrompt('ui_designer', ['path' => 'home_template']);

echo "Verification complete.\n";
