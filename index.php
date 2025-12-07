<?php

// add class autoloading
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/class/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        include $file;
    }
});

// start the application
$app = new \GaiaAlpha\App();
$app->run();


