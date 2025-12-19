<?php

use GaiaAlpha\Hook;

// Register CLI Command
Hook::add('cli_resolve_command', function ($current, $group, $parts) {
    if ($group === 'mcp') {
        return McpServer\Cli\McpCommands::class;
    }
    return $current;
});
