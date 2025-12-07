<?php

namespace GaiaAlpha;

use PDO;
use PDOException;

class Database
{
    private ?PDO $pdo = null;

    public function __construct(string $dbPath)
    {
        try {
            $this->pdo = new PDO("sqlite:" . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function ensureSchema(): void
    {
        $commands = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                level INTEGER DEFAULT 10
            )",
            "CREATE TABLE IF NOT EXISTS todos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                completed INTEGER DEFAULT 0,
                FOREIGN KEY(user_id) REFERENCES users(id)
            )"
        ];

        foreach ($commands as $command) {
            $this->pdo->exec($command);
        }

        // Migration for existing databases
        $columnsToAdd = [
            'users' => [
                'level' => 'INTEGER DEFAULT 10',
                'created_at' => 'DATETIME',
                'updated_at' => 'DATETIME'
            ],
            'todos' => [
                'created_at' => 'DATETIME',
                'updated_at' => 'DATETIME'
            ]
        ];

        foreach ($columnsToAdd as $table => $columns) {
            foreach ($columns as $column => $definition) {
                try {
                    $this->pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
                } catch (\PDOException $e) {
                    // Column likely already exists, ignore
                }
            }
        }

        // Backfill timestamps
        try {
            $this->pdo->exec("UPDATE users SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL");
            $this->pdo->exec("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL");
            $this->pdo->exec("UPDATE todos SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL");
            $this->pdo->exec("UPDATE todos SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL");
        } catch (\PDOException $e) {
            // Ignore for now
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
