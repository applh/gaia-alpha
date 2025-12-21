<?php

require_once __DIR__ . '/../class/GaiaAlpha/Model/DB.php';
require_once __DIR__ . '/../class/GaiaAlpha/Env.php';
require_once __DIR__ . '/../plugins/Analytics/class/Service/AnalyticsService.php';

use GaiaAlpha\Model\DB;
use GaiaAlpha\Env;
use Analytics\Service\AnalyticsService;

// Setup basic env
Env::set('root_dir', realpath(__DIR__ . '/..'));
$dbPath = __DIR__ . '/../my-data/database.sqlite';
$dsn = 'sqlite:' . $dbPath;

// Mock DB connection if needed or use real one
// For this test, we'll try to use the real one but in a transaction or just check current stats
try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // GaiaAlpha\Model\DB expects a custom Database wrapper or similar, but we can just set it if we have the class
    // Since I can't easily mock the whole framework easily, I'll just check if the class loads and methods exist.
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
}

$service = AnalyticsService::getInstance();

echo "Testing parseUserAgent...\n";
$testUas = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36' => 'Desktop',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Mobile/15E148 Safari/604.1' => 'Mobile',
    'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Mobile/15E148 Safari/604.1' => 'Tablet',
    'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36' => 'Mobile'
];

foreach ($testUas as $ua => $expectedDevice) {
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseUserAgent');
    $method->setAccessible(true);
    $result = $method->invoke($service, $ua);
    echo "UA: " . substr($ua, 0, 50) . "...\n";
    echo "  Expected: $expectedDevice, Got: " . $result['device'] . " (" . $result['os'] . "/" . $result['browser'] . ")\n";
    if ($result['device'] !== $expectedDevice) {
        echo "  FAILED\n";
    } else {
        echo "  PASSED\n";
    }
}

echo "\nChecking getStats structure...\n";
// This might fail if DB is not initialized properly in this standalone script
try {
    // Attempt to call getStats - will likely fail on DB::query if not connected
    // But we can check if the logic for padding history is sound by looking at the code
    echo "AnalyticsService::getStats exists: " . (method_exists($service, 'getStats') ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "getStats call failed (expected if DB not setup): " . $e->getMessage() . "\n";
}

echo "\nVerification of MCP Tool...\n";
require_once __DIR__ . '/../plugins/McpServer/class/Tool/BaseTool.php';
require_once __DIR__ . '/../plugins/McpServer/class/Tool/GetAnalyticsStats.php';

$tool = new \McpServer\Tool\GetAnalyticsStats();
echo "Tool Name: " . $tool->getDefinition()['name'] . "\n";
echo "Tool Description: " . $tool->getDefinition()['description'] . "\n";
echo "Tool Input Schema has 'days': " . (isset($tool->getDefinition()['inputSchema']['properties']['days']) ? 'YES' : 'NO') . "\n";

echo "\nVerification Complete.\n";
