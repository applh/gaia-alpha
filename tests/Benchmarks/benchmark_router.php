<?php
require_once __DIR__ . '/../../class/GaiaAlpha/Router.php';
require_once __DIR__ . '/../../class/GaiaAlpha/Hook.php';

use GaiaAlpha\Router;
use GaiaAlpha\Hook;

// Mock Hook to prevent errors
class MockHook
{
    public static function run($name, ...$args)
    {
    }
}

// Populate Router with many routes
$routeCount = 2000;
echo "Registering $routeCount routes...\n";

$start = microtime(true);
for ($i = 0; $i < $routeCount; $i++) {
    Router::get("/route/static/$i", function () { });
    Router::get("/route/dynamic/$i/(\d+)", function () { });
}
// Add target routes
Router::get("/target/first", function () {
    return "first";
});
Router::get("/target/last", function () {
    return "last";
});
$registrationTime = microtime(true) - $start;

echo "Registration took: " . number_format($registrationTime, 4) . "s\n";

// Benchmark Dispatch
$iterations = 1000;
echo "Benchmarking $iterations dispatches...\n";

$tests = [
    'First Route' => '/target/first', // Will be at end of list actually because we added it last? No, we added it after loop efficiently? check implementation. default implementation appends.
    // Wait, I added target/first AFTER the loop. So it's at index 2000.
    // Let's add a route at the very beginning manually to test best case if we want,
    // but typically we care about average/worst case.

    // Let's re-structure to test specific positions if the router is linear
];

// Reset router for clean test
$ref = new ReflectionClass('GaiaAlpha\Router');
$props = $ref->getStaticProperties();
// We can't easily reset private static without reflection hack or just running separate process.
// For this script, we'll just respect current state.
// Current state: 2000 * 2 routes added. Then target/first, then target/last.
// Total ~4002 routes.

// Case 1: Early match (if we added it first) - but we didn't.
// Case 2: Late match (added last)
// Case 3: No match

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    Router::dispatch('GET', '/target/last');
}
$duration = microtime(true) - $start;
echo "Dispatch (Last Route - Worst Case Linear): " . number_format($duration, 4) . "s (Avg: " . number_format($duration / $iterations * 1000, 3) . "ms)\n";

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    Router::dispatch('GET', '/route/static/0');
}
$duration = microtime(true) - $start;
echo "Dispatch (First Route - Best Case Linear): " . number_format($duration, 4) . "s (Avg: " . number_format($duration / $iterations * 1000, 3) . "ms)\n";


$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    Router::dispatch('GET', '/non/existent/route');
}
$duration = microtime(true) - $start;
echo "Dispatch (404 - Full Scan): " . number_format($duration, 4) . "s (Avg: " . number_format($duration / $iterations * 1000, 3) . "ms)\n";

