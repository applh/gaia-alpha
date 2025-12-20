<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Env;

class DbMigrationAssistant extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'db_migration_assistant',
            'description' => 'Generate a SQL migration script based on a description of changes',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'table' => ['type' => 'string', 'description' => 'Target table name'],
                    'description' => ['type' => 'string', 'description' => 'Description of the changes (e.g. \"Add a category column and an index on created_at\")'],
                    'apply' => ['type' => 'boolean', 'description' => 'Whether to automatically create the migration file in templates/sql/migrations', 'default' => false]
                ],
                'required' => ['table', 'description']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $table = $arguments['table'];
        $desc = $arguments['description'];
        $apply = $arguments['apply'] ?? false;

        // In a real scenario, this would use an LLM or a rule-based generator.
        // For now, we simulate the generation.

        $sql = "-- Migration for table: $table\n";
        $sql .= "-- Description: $desc\n";

        if (str_contains(strtolower($desc), 'add column') || str_contains(strtolower($desc), 'add a column')) {
            // Very simple heuristic for simulation
            preg_match('/add (a )?(\w+) column/', strtolower($desc), $matches);
            $colName = $matches[2] ?? 'new_column';
            $sql .= "ALTER TABLE $table ADD COLUMN $colName TEXT;\n";
        } else {
            $sql .= "/* Suggested change: $desc */\n";
            $sql .= "-- ALTER TABLE $table ADD COLUMN ...;\n";
        }

        if ($apply) {
            $migrationsDir = Env::get('root_dir') . '/templates/sql/migrations';
            if (!is_dir($migrationsDir)) {
                mkdir($migrationsDir, 0755, true);
            }
            $filename = date('YmdHis') . "_update_$table.sql";
            file_put_contents($migrationsDir . '/' . $filename, $sql);
            return $this->resultText("Migration generated and saved to 'templates/sql/migrations/$filename':\n\n$sql");
        }

        return $this->resultText("Suggested SQL Migration:\n\n$sql");
    }
}
