<?php

namespace GaiaAlpha;

use PDO;
use PDOException;

use GaiaAlpha\Env;



class Database
{
    private ?PDO $pdo = null;

    public function __construct(string $dsn, ?string $user = null, ?string $pass = null)
    {
        // Let PDOException bubble up so it can be caught by the caller
        $this->pdo = new PDO($dsn, $user, $pass);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function ensureSchema(): void
    {
        $sqlFiles = [];

        // 1. Core Schemas
        $sqlDir = Env::get('root_dir') . '/templates/sql';
        $coreFiles = glob($sqlDir . '/*.sql') ?: [];
        sort($coreFiles);
        $sqlFiles = array_merge($sqlFiles, $coreFiles);

        // 2. Plugin Schemas
        $activePlugins = [];
        $activePluginsFile = Env::get('path_data') . '/active_plugins.json';
        if (file_exists($activePluginsFile)) {
            $activePlugins = json_decode(file_get_contents($activePluginsFile), true) ?: [];
        }

        $pluginRoots = [
            Env::get('path_data') . '/plugins',
            Env::get('root_dir') . '/plugins'
        ];

        foreach ($pluginRoots as $dir) {
            foreach ($activePlugins as $pluginName) {
                $schemaPath = $dir . '/' . $pluginName . '/schema.sql';
                if (file_exists($schemaPath)) {
                    if (!in_array($schemaPath, $sqlFiles)) {
                        $sqlFiles[] = $schemaPath;
                    }
                }
            }
        }

        if (empty($sqlFiles)) {
            return;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        foreach ($sqlFiles as $filePath) {
            $this->runSqlFile($filePath, $driver);
        }

        $this->runMigrations();
    }

    public function ensurePluginSchema(string $pluginName): void
    {
        $pluginRoots = [
            Env::get('path_data') . '/plugins',
            Env::get('root_dir') . '/plugins'
        ];

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        foreach ($pluginRoots as $dir) {
            $schemaPath = $dir . '/' . $pluginName . '/schema.sql';
            if (file_exists($schemaPath)) {
                $this->runSqlFile($schemaPath, $driver);
            }
        }
    }

    private function runSqlFile(string $filePath, string $driver): void
    {
        if (file_exists($filePath)) {
            $sqlContent = file_get_contents($filePath);
            $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);
            $statements = $this->splitSql($sqlContent);

            foreach ($statements as $statement) {
                try {
                    $processedSql = $this->transformSql($statement, $driver);
                    $this->pdo->exec($processedSql);
                } catch (PDOException $e) {
                    // Column/constraint likely already exists, ignore
                }
            }
        }
    }

    public function runMigrations(): void
    {
        // Run migrations for existing databases
        $migrationsDir = Env::get('root_dir') . '/templates/sql/migrations';
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if (is_dir($migrationsDir)) {
            $migrationFiles = glob($migrationsDir . '/*.sql');
            sort($migrationFiles); // Ensure migrations run in order

            foreach ($migrationFiles as $migrationFile) {
                $sqlStatements = file_get_contents($migrationFile);

                // Remove comments
                $sqlStatements = preg_replace('/^--.*$/m', '', $sqlStatements);

                $statements = $this->splitSql($sqlStatements);

                foreach ($statements as $statement) {
                    try {
                        $processedSql = $this->transformSql($statement, $driver);
                        $this->pdo->exec($processedSql);
                    } catch (PDOException $e) {
                        // Column/constraint likely already exists, ignore
                    }
                }
            }
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getTables(): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            return $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($driver === 'mysql') {
            return $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($driver === 'pgsql') {
            return $this->pdo->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
        }
        return [];
    }

    public function getTableSchema(string $tableName): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            return $this->pdo->query("PRAGMA table_info(" . $tableName . ")")->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($driver === 'mysql') {
            return $this->pdo->query("DESCRIBE " . $tableName)->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($driver === 'pgsql') {
            $sql = "SELECT column_name as \"name\", data_type as \"type\", is_nullable as \"null\", column_default as \"default\" 
                    FROM information_schema.columns 
                    WHERE table_name = " . $this->pdo->quote($tableName) . "
                    ORDER BY ordinal_position";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function getCreateTableSql(string $tableName): string
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = $this->pdo->query("SELECT sql FROM sqlite_master WHERE name = " . $this->pdo->quote($tableName))->fetchColumn();
            return $sql ?: "";
        }

        // Reconstruct SQLite-compatible CREATE TABLE for other drivers
        $columns = $this->getTableSchema($tableName);
        $defs = [];
        foreach ($columns as $col) {
            $name = $col['name'] ?? $col['Field'];
            $type = strtoupper($col['type'] ?? $col['Type']);

            // Normalize types back to SQLite-ish
            if (str_contains($type, 'INT'))
                $type = 'INTEGER';
            if (str_contains($type, 'CHAR') || str_contains($type, 'TEXT'))
                $type = 'TEXT';
            if (str_contains($type, 'TIME') || str_contains($type, 'DATE'))
                $type = 'DATETIME';

            $def = $name . " " . $type;

            // Handle Primary Key and Auto Increment
            $isPk = false;
            if ($driver === 'mysql') {
                if (($col['Key'] ?? '') === 'PRI')
                    $isPk = true;
                if (str_contains($col['Extra'] ?? '', 'auto_increment'))
                    $def .= " PRIMARY KEY AUTOINCREMENT";
            } elseif ($driver === 'pgsql') {
                if (str_contains($col['default'] ?? '', 'nextval'))
                    $isPk = true;
                if ($isPk)
                    $def = $name . " INTEGER PRIMARY KEY AUTOINCREMENT";
            }

            if ($isPk && !str_contains($def, 'PRIMARY KEY')) {
                $def .= " PRIMARY KEY";
            }

            $defs[] = $def;
        }

        return "CREATE TABLE $tableName (" . implode(', ', $defs) . ")";
    }

    public function dump(string $outputFile): void
    {
        $tables = $this->getTables();
        $sql = "-- Gaia Alpha Database Dump\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Driver: " . $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n\n";

        foreach ($tables as $table) {
            $sql .= "DROP TABLE IF EXISTS " . $table . ";\n";
            $sql .= $this->getCreateTableSql($table) . ";\n\n";

            $stmt = $this->pdo->query("SELECT * FROM " . $table);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns = array_keys($row);
                $values = array_map(function ($val) {
                    if ($val === null)
                        return 'NULL';
                    return $this->pdo->quote($val);
                }, array_values($row));

                $sql .= "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }

        file_put_contents($outputFile, $sql);
    }

    public function import(string $inputFile): void
    {
        if (!file_exists($inputFile)) {
            throw new \RuntimeException("Import file not found: $inputFile");
        }

        $sqlContent = file_get_contents($inputFile);
        $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);

        $statements = $this->splitSql($sqlContent);

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        foreach ($statements as $statement) {
            $processedSql = $this->transformSql($statement, $driver);
            $this->pdo->exec($processedSql);
        }
    }

    private function splitSql(string $sql): array
    {
        $statements = [];
        $current = "";
        $inString = false;
        $stringChar = "";

        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }

            if ($char === ';' && !$inString) {
                if (trim($current) !== "") {
                    $statements[] = trim($current);
                }
                $current = "";
            } else {
                $current .= $char;
            }
        }

        if (trim($current) !== "") {
            $statements[] = trim($current);
        }

        return $statements;
    }

    private function transformSql(string $sql, string $driver): string
    {
        if ($driver === 'sqlite') {
            return $sql;
        }

        // Handle case where we might be importing from a dump that already has some driver-specific syntax
        // but we prioritize converting FROM SQLite format (Gaia-standard)

        if ($driver === 'mysql') {
            $sql = preg_replace(
                '/INTEGER PRIMARY KEY AUTOINCREMENT/i',
                'INT AUTO_INCREMENT PRIMARY KEY',
                $sql
            );
        } elseif ($driver === 'pgsql') {
            $sql = preg_replace(
                '/INTEGER PRIMARY KEY AUTOINCREMENT/i',
                'SERIAL PRIMARY KEY',
                $sql
            );

            $sql = preg_replace('/DATETIME/i', 'TIMESTAMP', $sql);
            $sql = preg_replace('/TINYINT/i', 'SMALLINT', $sql);
        }

        return $sql;
    }
}
