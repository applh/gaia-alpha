<?php

require_once __DIR__ . '/framework/TestRunner.php';

// Autoload core classes if usually done by index.php
// Here we might need a simpler autoloader for the app classes
spl_autoload_register(function ($class) {
    // Map GaiaAlpha\Foo -> class/GaiaAlpha/Foo.php
    $prefix = 'GaiaAlpha\\';
    $base_dir = __DIR__ . '/../class/GaiaAlpha/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) === 0) {
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }

    // Fallback for Plugin classes? 
    // Usually plugins are loaded via a specific mechanism.
    // We will need to stub/mock that or include plugin autoload logic here.
});

use GaiaAlpha\Tests\Framework\TestRunner;

$runner = new TestRunner();
$runner->run(__DIR__); // Scan 'tests' directory
