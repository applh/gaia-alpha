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
Hook::add('framework_load_controllers_after', function () {
    $controllers = Env::get('controllers');

    // Register SSE Controller
    $sseController = new McpServer\Controller\SseController();
    $controllers['mcp-sse'] = $sseController;

    Env::set('controllers', $controllers);

    // Register SSE routes
    \GaiaAlpha\Router::add('POST', '/@/mcp/session', [$sseController, 'createSession']);
    \GaiaAlpha\Router::add('POST', '/@/mcp/request', [$sseController, 'handleRequest']);
    \GaiaAlpha\Router::add('GET', '/@/mcp/stream', [$sseController, 'handleStream']);
});
