<?php

namespace McpServer\Resource;

use GaiaAlpha\Model\DB;

class DatabaseTables extends BaseResource
{
    public function getDefinition(): array
    {
        return [
            'uri' => 'cms://sites/{site}/database/tables',
            'name' => 'Site Database Tables',
            'mimeType' => 'application/json'
        ];
    }

    public function matches(string $uri): ?array
    {
        if (preg_match('#^cms://sites/([^/]+)/database/tables$#', $uri, $matches)) {
            return $matches;
        }
        return null;
    }

    public function read(string $uri, array $matches): array
    {
        $site = $matches[1];

        // Note: Site switching is globally handled in Server.php before calling read()
        // but since resources are matched by URI, we might need to switch here too
        // if Server.php doesn't do it.
        // In the original handleResourceRead, it switches.

        $tables = DB::getTables();
        $result = [];
        foreach ($tables as $table) {
            $result[] = [
                'name' => $table,
                'schema' => DB::getTableSchema($table),
                'count' => DB::getTableCount($table)
            ];
        }
        return $this->contents($uri, json_encode($result, JSON_PRETTY_PRINT));
    }
}
