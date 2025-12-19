<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\DB;

class DbQuery extends BaseTool
{
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
