<?php

namespace DatabaseConnections\Tool;

use McpServer\Tool\BaseTool;
use DatabaseConnections\Service\ConnectionManager;

class ListConnections extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'list_db_connections',
            'description' => 'List all configured database connections',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [],
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $manager = new ConnectionManager();
        $connections = $manager->listConnections();
        return $this->resultJson($connections);
    }
}
