<?php

require_once __DIR__ . '/framework/TestRunner.php';

require_once __DIR__ . '/../class/GaiaAlpha/App.php';
require_once __DIR__ . '/../class/GaiaAlpha/Env.php';
require_once __DIR__ . '/../class/GaiaAlpha/Hook.php';

// Setup Mock Env
\GaiaAlpha\Env::set('root_dir', realpath(__DIR__ . '/../'));
\GaiaAlpha\Env::set('path_data', realpath(__DIR__ . '/../my-data'));

// Register Autoloaders
\GaiaAlpha\App::registerAutoloaders();

use GaiaAlpha\Tests\Framework\TestRunner;

$runner = new TestRunner();
// Parse Arguments
$paths = [];
if (isset($argv) && count($argv) > 1) {
    for ($i = 1; $i < count($argv); $i++) {
        $paths[] = $argv[$i];
    }
}

if (empty($paths)) {
    // Default: Scan 'tests' directory and 'plugins' directory
    $paths = [
        __DIR__,
        realpath(__DIR__ . '/../plugins')
    ];
}

$runner->run($paths);
