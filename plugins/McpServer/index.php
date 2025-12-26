<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use McpServer\Controller\SseController;
use McpServer\Controller\McpStatsController;

// Register CLI Command
Hook::add('cli_resolve_command', function ($current, $group, $parts) {
    if ($group === 'mcp') {
        return McpServer\Cli\McpCommands::class;
    }
    return $current;
});

// Register Controllers and Routes
Hook::add('framework_load_controllers_after', function ($controllers) {
    \GaiaAlpha\Framework::registerController('mcp-sse', SseController::class);
    \GaiaAlpha\Framework::registerController('mcp-stats', McpStatsController::class);
});

// Register UI Component
\GaiaAlpha\UiManager::registerComponent(
    'mcp_dashboard',
    'plugins/McpServer/McpDashboard.js',
    true // Admin only
);

// Inject Menu Item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user']) && $data['user']['level'] >= 100) {
        if (!isset($data['user']['menu_items']) || !is_array($data['user']['menu_items'])) {
            $data['user']['menu_items'] = [];
        }

        $found = false;
        foreach ($data['user']['menu_items'] as $item) {
            if (isset($item['id']) && $item['id'] === 'grp-reports') {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['user']['menu_items'][] = [
                'id' => 'grp-reports',
                'label' => 'Reports',
                'icon' => 'bar-chart-2',
                'children' => []
            ];
        }
    }
    return $data;
}, 9);

// Register Logging (Pluggable)
Hook::add('mcp_request_handled', [McpServer\Service\McpLogger::class, 'logRequest']);
