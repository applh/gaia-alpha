<?php

namespace McpServer\Cli;

use GaiaAlpha\Cli\Output;
use McpServer\Server;

class McpCommands
{
    public static function handleServer()
    {
        // Don't output any text prefix/logs to STDOUT because it breaks JSON-RPC
        // We might want to re-route generic Output::info to stderr or file for this session?
        // For now, we assume the Server class handles stream isolation.

        $server = new Server();
        $server->runStdio();
    }
}
