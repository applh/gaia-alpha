<?php

namespace GaiaAlpha;

use PDO;
use PDOException;

use GaiaAlpha\Env;

use GaiaAlpha\Database\LoggedPDO;

class Database
{
    private ?PDO $pdo = null;

    public function __construct(string $dsn)
    {
        try {
            $this->pdo = new LoggedPDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function ensureSchema(): void
    {
        // Load SQL commands from template files
        $sqlDir = Env::get('root_dir') . '/templates/sql';

        // Scan for schema files and sort them
        // Use numbered prefixes for explicit order: 001_users.sql, 002_todos.sql, etc.
        // Only scans files directly in /templates/sql/, not subdirectories like /migrations/
        $sqlFiles = glob($sqlDir . '/*.sql');

        if ($sqlFiles === false || empty($sqlFiles)) {
            throw new \RuntimeException("No SQL schema files found in: $sqlDir");
        }

        sort($sqlFiles); // Ensures consistent alphabetical order (001_users.sql, 002_todos.sql, etc.)

        $commands = [];
        foreach ($sqlFiles as $filePath) {
            if (file_exists($filePath)) {
                $commands[] = file_get_contents($filePath);
            } else {
                throw new \RuntimeException("SQL template file not found: $filePath");
            }
        }

        foreach ($commands as $command) {
            $this->pdo->exec($command);
        }


        // Run migrations for existing databases
        $migrationsDir = Env::get('root_dir') . '/templates/sql/migrations';

        if (is_dir($migrationsDir)) {
            $migrationFiles = glob($migrationsDir . '/*.sql');
            sort($migrationFiles); // Ensure migrations run in order

            foreach ($migrationFiles as $migrationFile) {
                $sqlStatements = file_get_contents($migrationFile);

                // Split by semicolon to handle multiple statements
                $statements = array_filter(
                    array_map('trim', explode(';', $sqlStatements)),
                    function ($stmt) {
                        // Filter out empty statements and comments
                        return !empty($stmt) && !str_starts_with($stmt, '--');
                    }
                );

                foreach ($statements as $statement) {
                    try {
                        $this->pdo->exec($statement);
                    } catch (\PDOException $e) {
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
}
