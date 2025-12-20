<?php

namespace DatabaseConnections\Tool;

use McpServer\Tool\BaseTool;
use DatabaseConnections\Service\ConnectionManager;

class ExecuteExternalQuery extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'execute_external_query',
            'description' => 'Execute a SQL query against an external database connection',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'connection_id' => ['type' => 'integer', 'description' => 'ID of the connection to use'],
                    'query' => ['type' => 'string', 'description' => 'SQL query to execute']
                ],
                'required' => ['connection_id', 'query']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $manager = new ConnectionManager();
        $id = $arguments['connection_id'];
        $sql = $arguments['query'];

        try {
            $result = $manager->executeQuery($id, $sql);
            return $this->resultJson($result);
        } catch (\Exception $e) {
            throw new \Exception("Query execution failed: " . $e->getMessage());
        }
    }
}
