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

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        foreach ($sqlFiles as $filePath) {
            if (file_exists($filePath)) {
                $sqlContent = file_get_contents($filePath);

                // Remove comments
                $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);

                // Split by semicolon to handle multiple statements
                $statements = array_filter(
                    array_map('trim', explode(';', $sqlContent)),
                    fn($stmt) => !empty($stmt)
                );

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

        $this->runMigrations();
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

                // Split by semicolon to handle multiple statements
                $statements = array_filter(
                    array_map('trim', explode(';', $sqlStatements)),
                    fn($stmt) => !empty($stmt)
                );

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

    private function transformSql(string $sql, string $driver): string
    {
        if ($driver === 'sqlite') {
            return $sql;
        }

        // Common replacements
        if ($driver === 'mysql') {
            // SQLite: INTEGER PRIMARY KEY AUTOINCREMENT
            // MySQL:  INT AUTO_INCREMENT PRIMARY KEY
            $sql = preg_replace(
                '/INTEGER PRIMARY KEY AUTOINCREMENT/i',
                'INT AUTO_INCREMENT PRIMARY KEY',
                $sql
            );

            // Fix text defaults incompatible with blob/text in some mysql versions if not careful, 
            // but primarily we valid syntax. 
            // Also handle "DATETIME DEFAULT CURRENT_TIMESTAMP" which is valid in MySQL 5.6.5+ 

        } elseif ($driver === 'pgsql') {
            // PostgreSQL: SERIAL PRIMARY KEY
            // Note: Postgres uses SERIAL which implies INTEGER PRIMARY KEY DEFAULT nextval(...)
            $sql = preg_replace(
                '/INTEGER PRIMARY KEY AUTOINCREMENT/i',
                'SERIAL PRIMARY KEY',
                $sql
            );

            // Postgres logic for "DATETIME" -> "TIMESTAMP"? 
            // Actually "DATETIME" is not a native Postgres type (it's TIMESTAMP).
            $sql = preg_replace('/DATETIME/i', 'TIMESTAMP', $sql);

            // "TINYINT" -> "SMALLINT"
            $sql = preg_replace('/TINYINT/i', 'SMALLINT', $sql);
        }

        return $sql;
    }
}
