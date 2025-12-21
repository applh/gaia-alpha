<?php
// tests/verify_canonical.php

require_once __DIR__ . '/../class/GaiaAlpha/App.php';
\GaiaAlpha\App::registerAutoloaders();

use GaiaAlpha\Model\DB;
use GaiaAlpha\Model\Page;
use GaiaAlpha\Controller\ViewController;
use GaiaAlpha\Request;
use GaiaAlpha\Env;

// Mock environment
$rootDir = realpath(__DIR__ . '/..');
Env::set('root_dir', $rootDir);
Env::set('path_data', $rootDir . '/my-data');
\GaiaAlpha\SiteManager::resolve();
DB::connect();

echo "Running Canonical URL Verification...\n\n";

// 1. Test Data Preparation
$userId = 1; // Default admin
$testSlugAuto = 'test-canonical-auto-' . uniqid();
$testSlugManual = 'test-canonical-manual-' . uniqid();
$manualUrl = 'https://external-site.com/canonical-page';

echo "Cleaning up any old test pages...\n";
DB::execute("DELETE FROM cms_pages WHERE slug IN (?, ?)", [$testSlugAuto, $testSlugManual]);

echo "Creating test pages...\n";
Page::create($userId, [
    'title' => 'Test Auto Canonical',
    'slug' => $testSlugAuto,
    'content' => 'Auto content'
]);

Page::create($userId, [
    'title' => 'Test Manual Canonical',
    'slug' => $testSlugManual,
    'content' => 'Manual content',
    'canonical_url' => $manualUrl
]);

// 2. Verify Database Storage
echo "Verifying database storage...\n";
$pageAuto = Page::findBySlug($testSlugAuto);
$pageManual = Page::findBySlug($testSlugManual);

if ($pageAuto && array_key_exists('canonical_url', $pageAuto)) {
    echo "✓ Automatic canonical URL field exists in DB.\n";
} else {
    echo "✗ Automatic canonical URL field missing in DB.\n";
    exit(1);
}

if ($pageManual && $pageManual['canonical_url'] === $manualUrl) {
    echo "✓ Manual canonical URL stored correctly: " . $pageManual['canonical_url'] . "\n";
} else {
    echo "✗ Manual canonical URL storage failed.\n";
    exit(1);
}

// 3. Verify Logic in ViewController
echo "\nVerifying logic in ViewController...\n";

// Mock request
Request::mock([], [], [], [
    'HTTP_HOST' => 'localhost:8000',
    'HTTPS' => 'off'
]);

$controller = new ViewController();

// Using reflection to test private method if needed, or just test its output via render if possible.
// Since it's easier to test the method directly if made public, but I'll use reflection.
$reflection = new ReflectionClass(ViewController::class);
$method = $reflection->getMethod('getCanonicalUrl');
$method->setAccessible(true);

$canonicalAuto = $method->invoke($controller, $pageAuto, $testSlugAuto);
$expectedAuto = 'http://localhost:8000/' . $testSlugAuto;

if ($canonicalAuto === $expectedAuto) {
    echo "✓ Automatic canonical URL logic correct: $canonicalAuto\n";
} else {
    echo "✗ Automatic canonical URL logic failed. Expected $expectedAuto, got $canonicalAuto\n";
    exit(1);
}

$canonicalManual = $method->invoke($controller, $pageManual, $testSlugManual);
if ($canonicalManual === $manualUrl) {
    echo "✓ Manual canonical URL override logic correct: $canonicalManual\n";
} else {
    echo "✗ Manual canonical URL override logic failed. Expected $manualUrl, got $canonicalManual\n";
    exit(1);
}

// 4. Test Home Page
$pageHome = ['slug' => 'home', 'canonical_url' => null];
$canonicalHome = $method->invoke($controller, $pageHome, '/');
$expectedHome = 'http://localhost:8000';

if ($canonicalHome === $expectedHome) {
    echo "✓ Home page canonical URL logic correct: $canonicalHome\n";
} else {
    echo "✗ Home page canonical URL logic failed. Expected $expectedHome, got $canonicalHome\n";
    exit(1);
}

echo "\nAll Canonical URL tests passed!\n";

// Cleanup
DB::execute("DELETE FROM cms_pages WHERE slug IN (?, ?)", [$testSlugAuto, $testSlugManual]);
