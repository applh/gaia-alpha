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
        try {
            $this->pdo = new PDO($dsn, $user, $pass);
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
        $sqlFiles = glob($sqlDir . '/*.sql');

        if ($sqlFiles === false || empty($sqlFiles)) {
            throw new \RuntimeException("No SQL schema files found in: $sqlDir");
        }

        sort($sqlFiles);

        foreach ($sqlFiles as $filePath) {
            if (file_exists($filePath)) {
                $sqlContent = file_get_contents($filePath);

                // Split by semicolon to handle multiple statements
                // reusing logic similar to runMigrations
                $statements = array_filter(
                    array_map('trim', explode(';', $sqlContent)),
                    function ($stmt) {
                        return !empty($stmt) && !str_starts_with($stmt, '--');
                    }
                );

                foreach ($statements as $statement) {
                    try {
                        $this->pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore "table already exists" type errors
                        // Making this idempotent allows safe re-runs
                    }
                }
            }
        }

        $this->runMigrations();
    }

    public function runMigrations(): void
    {
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
}
