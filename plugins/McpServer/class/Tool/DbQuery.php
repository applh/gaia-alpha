<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\DB;

class DbQuery extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'db_query',
            'description' => 'Execute a read-only SQL query on the selected site database',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'sql' => ['type' => 'string', 'description' => 'SQL query (must be a SELECT statement)'],
                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)']
                ],
                'required' => ['sql']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $sql = $arguments['sql'] ?? null;
        if (!$sql) {
            throw new \Exception("SQL query is required.");
        }

        if (stripos(trim($sql), 'SELECT') !== 0) {
            throw new \Exception("Only SELECT queries are allowed via this tool.");
        }
        $results = DB::fetchAll($sql);
        return $this->resultJson($results);
    }
}
