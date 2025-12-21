<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;

// Register CLI Command
Hook::add('cli_resolve_command', function ($current, $group, $parts) {
    if ($group === 'mcp') {
        return McpServer\Cli\McpCommands::class;
    }
    return $current;
});

// Register SSE Controller and Routes
// Register SSE Controller and Routes
Hook::add('framework_load_controllers_after', function ($controllers) {
    $controllers = Env::get('controllers');
    if (class_exists(McpServer\Controller\SseController::class)) {
        $sseController = new McpServer\Controller\SseController();
        if (method_exists($sseController, 'registerRoutes')) {
            $sseController->registerRoutes();
        }
        $controllers['mcp-sse'] = $sseController;
        Env::set('controllers', $controllers);
    }
});
