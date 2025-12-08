<?php

// Load local configuration if exists
$configFile = dirname(__DIR__) . '/my-config.php';
if (file_exists($configFile)) {
    include_once $configFile;
}

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/GaiaAlpha/' . str_replace('GaiaAlpha\\', '', $class) . '.php';
    // Wait, the previous autoloader was:
    // $file = __DIR__ . '/class/' . str_replace('\\', '/', $class) . '.php';
    // But this file will be in class/autoload.php, so __DIR__ is class/.

    // Implementation:
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php'; // Since GaiaAlpha is mapped to class/GaiaAlpha

    // Actually, let's keep it simple and consistent with the project structure.
    // Project root is ../
    // Classes are in class/

    // Let's use the logic from index.php but adjusted for location.
    // If this file is at class/autoload.php:
    $file = __DIR__ . '/' . str_replace('GaiaAlpha\\', 'GaiaAlpha/', $class) . '.php';

    // Let's check the previous logic in index.php (Step 1735):
    // $file = __DIR__ . '/class/' . str_replace('\\', '/', $class) . '.php';
    // This was in root index.php.

    // So if I put this in class/autoload.php:
    $baseDir = dirname(__DIR__) . '/class/';
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        include $file;
    }
});
