<?php

namespace DatabaseConnections\Tool;

use McpServer\Tool\BaseTool;
use DatabaseConnections\Service\ConnectionManager;

class TestConnection extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'test_db_connection',
            'description' => 'Test a database connection configuration',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => 'Existing connection ID'],
                    'type' => ['type' => 'string', 'enum' => ['mysql', 'pgsql', 'sqlite']],
                    'host' => ['type' => 'string'],
                    'database' => ['type' => 'string'],
                    'username' => ['type' => 'string'],
                    'password' => ['type' => 'string']
                ]
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $manager = new ConnectionManager();

        try {
            if (isset($arguments['id'])) {
                $conn = $manager->getConnection($arguments['id']);
                // Temporarily inject password back if testing existing? 
                // Actually connectionManager needs password.
                // Re-fetching inside manager for execution is fine, but for test?
                // ConnectionManager::testConnection takes raw data array.
                // If ID is provided, we should probably fetch it.
                // But wait, listConnections masks password.
                // So if we pass ID, we relying on DB stored password.
                // But testConnection(array $data) builds DSN from data.

                // Let's refactor our approach slightly:
                // If ID is passed, fetch from DB.
                if ($conn) {
                    $manager->testConnection($conn);
                    return $this->resultText("Connection ID {$arguments['id']} is valid.");
                } else {
                    throw new \Exception("Connection ID not found");
                }
            }

            // Testing raw params
            $manager->testConnection($arguments);
            return $this->resultText("Connection configuration is valid.");

        } catch (\Exception $e) {
            // Throwing exception returns error to MCP client
            throw new \Exception("Connection failed: " . $e->getMessage());
        }
    }
}
