<?php
require_once __DIR__ . '/../../class/GaiaAlpha/App.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Env.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Hook.php';

\GaiaAlpha\Env::set('autoloaders', [
    [\GaiaAlpha\App::class, 'autoloadFramework'],
    [\GaiaAlpha\App::class, 'autoloadPlugins'],
    [\GaiaAlpha\App::class, 'autoloadAliases']
]);
\GaiaAlpha\App::registerAutoloaders();
\GaiaAlpha\App::web_setup(__DIR__ . '/../..');

use GaiaAlpha\Model\DB;
use GaiaAlpha\Model\Page;
use GaiaAlpha\Service\SchemaService;

// Setup test data
DB::connect();
$userId = 1;
$pageData = [
    'title' => 'Test Article',
    'slug' => 'test-article-' . time(),
    'content' => 'This is a test article.',
    'cat' => 'page',
    'schema_type' => 'Article',
    'schema_data' => json_encode(['wordCount' => 1234])
];

$pageId = Page::create($userId, $pageData);
echo "Created test page with ID: $pageId\n";

$page = Page::findBySlug($pageData['slug']);

$globalSettings = [
    'site_title' => 'Gaia Test Site',
    'site_description' => 'A test environment'
];

$jsonLd = SchemaService::generateJsonLd($page, $globalSettings);
echo "Generated JSON-LD:\n$jsonLd\n";

// Basic assertions
$data = json_decode($jsonLd, true);
if ($data['@type'] === 'Article' && $data['name'] === 'Test Article' && $data['wordCount'] === 1234) {
    echo "SUCCESS: JSON-LD matches expected structure.\n";
} else {
    echo "FAILURE: JSON-LD structure mismatch.\n";
    exit(1);
}

// Cleanup
DB::execute("DELETE FROM cms_pages WHERE id = ?", [$pageId]);
echo "Deleted test page.\n";
