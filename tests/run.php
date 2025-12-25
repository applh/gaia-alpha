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
// Scan 'tests' directory and 'plugins' directory
$runner->run([
    __DIR__,
    realpath(__DIR__ . '/../plugins')
]);
