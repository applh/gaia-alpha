<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Plugins\Mail\Controller\MailController;

// 1. Register Custom Autoloader for this plugin's namespace
// Because our namespace 'GaiaAlpha\Plugins\Mail' doesn't match the default 'PluginName\...' pattern exactly 
// (or rather to be safe/explicit since we are 'Core').
spl_autoload_register(function ($class) {
    $prefix = 'GaiaAlpha\\Plugins\\Mail\\';
    $base_dir = __DIR__ . '/class/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// 2. Register Controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    if (class_exists(MailController::class)) {
        $controller = new MailController();
        // Register as 'mail' so routes like /@/admin/mail/... work if not using attribute routing
        $controllers['mail'] = $controller;

        // Update controllers list in Env if needed (usually passed by reference, but Env update is safe)
        // Note: The hook receives $controllers by value/copy often in this framework depending on implementation, 
        // but looking at Console index.php, it updates Env::set('controllers', $controllers).
        \GaiaAlpha\Env::set('controllers', $controllers);
    }
    return $controllers;
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent('mail/inbox', 'plugins/Mail/MailPanel.js', true);

// 3. Inject Admin Menu Item

