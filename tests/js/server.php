<?php
// tests/js/server.php

$port = 8001;
$docroot = realpath(__DIR__ . '/../../'); // Project root
$testDir = __DIR__; // tests/js directory

// --- ROUTER MODE (Inside the web server) ---
if (php_sapi_name() !== 'cli' || isset($_SERVER['GATEWAY_INTERFACE'])) {
    $uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($uri, PHP_URL_PATH);

    // 1. API: List Tests
    if ($path === '/api/tests') {
        header('Content-Type: application/json');
        $tests = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.js')) {
                // Get relative path from tests/js/
                $relativePath = substr($file->getPathname(), strlen($testDir) + 1);
                $tests[] = [
                    'path' => $relativePath,
                    'name' => str_replace('Test.js', '', $relativePath) // Simple name
                ];
            }
        }
        // sort for consistency
        sort($tests);
        echo json_encode($tests);
        exit;
    }

    // 2. API: Report Results
    if ($path === '/api/report') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);
        $type = $payload['type'] ?? 'info';
        $data = $payload['data'] ?? [];

        // Log to file
        $logFile = $testDir . '/test_run.log';
        $logEntry = "[" . date('H:i:s') . "] [$type] " . json_encode($data) . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        // Log to Console (Stderr)
        if ($type === 'progression') {
            $statusIcon = ($data['status'] === 'passed') ? "✅" : "❌";
            $error = isset($data['error']) ? " (Error: {$data['error']})" : "";
            error_log("$statusIcon {$data['suite']} > {$data['test']}$error");
        } elseif ($type === 'start') {
            error_log("\n🚀 Starting JS Tests: {$data['total']} tests total\n");
        } elseif ($type === 'result') {
            error_log("\n🏁 JS Tests Finished: {$data['passedCount']} Passed, {$data['failedCount']} Failed\n");
            file_put_contents($testDir . '/results.json', json_encode($data, JSON_PRETTY_PRINT));
        }

        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        exit;
    }

    // 3. Serve Runner UI
    if ($path === '/' || $path === '/index.html') {
        require $testDir . '/runner.php';
        exit;
    }

    // 4. Serve Static Files (Resources, Plugins, Tests)
    // Map /tests/js/* back to local dir
    if (strpos($path, '/tests/js/') === 0) {
        $localPath = $testDir . substr($path, strlen('/tests/js'));
        if (file_exists($localPath)) {
            // Simple mime type detection
            if (str_ends_with($localPath, '.js'))
                header('Content-Type: application/javascript');
            if (str_ends_with($localPath, '.css'))
                header('Content-Type: text/css');
            readfile($localPath);
            exit;
        }
    }

    // Default Fallback: Let PHP built-in server handle it (usually looks relative to docroot starting dir)
    // But we are running from project root via -S, so request to /resources/... should resolve naturally if we return false.
    return false;
}

// --- CLI MODE (Starting the server) ---
echo "\n";
echo "-------------------------------------------------------\n";
echo "  Gaia Alpha Test Server (PHP-Powered)\n";
echo "-------------------------------------------------------\n";
echo "  Address:       http://localhost:$port\n";
echo "  API:           http://localhost:$port/api/tests\n";
echo "-------------------------------------------------------\n\n";

// Start server from PROJECT ROOT so /resources/ paths work
passthru("php -S localhost:$port -t " . escapeshellarg($docroot) . " " . escapeshellarg(__FILE__));
