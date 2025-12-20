<?php

namespace DatabaseConnections\Service;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Database;
use Exception;
use PDO;

class ConnectionManager
{
    private $allowedTypes = ['mysql', 'pgsql', 'sqlite'];

    public function listConnections(): array
    {
        $connections = DB::fetchAll("SELECT * FROM cms_db_connections ORDER BY name ASC");

        // Mask passwords
        foreach ($connections as &$conn) {
            unset($conn['password']);
        }

        return $connections;
    }

    public function getConnection(int $id): ?array
    {
        $conn = DB::fetch("SELECT * FROM cms_db_connections WHERE id = ?", [$id]);
        return $conn ?: null;
    }

    public function saveConnection(array $data): int
    {
        $this->validateConnectionData($data);

        // Optional: Encrypt password here if needed. 
        // For this implementation, we store as plain text per plan, but this is hook-able.

        if (isset($data['id']) && $data['id']) {
            return $this->updateConnection($data);
        } else {
            return $this->createConnection($data);
        }
    }

    public function deleteConnection(int $id): void
    {
        DB::execute("DELETE FROM cms_db_connections WHERE id = ?", [$id]);
    }

    public function testConnection(array $data): bool
    {
        $dsn = $this->buildDsn($data);

        try {
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            $pdo = new PDO($dsn, $data['username'] ?? null, $data['password'] ?? null, $options);
            return true;
        } catch (Exception $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    public function executeQuery(int $connectionId, string $sql): array
    {
        $conn = $this->getConnectionWithPassword($connectionId);
        if (!$conn) {
            throw new Exception("Connection not found");
        }

        $dsn = $this->buildDsn($conn);

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
            $pdo = new PDO($dsn, $conn['username'] ?? null, $conn['password'] ?? null, $options);

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            // Check if it's a SELECT query
            if (stripos(trim($sql), 'SELECT') === 0 || stripos(trim($sql), 'SHOW') === 0) {
                return [
                    'type' => 'read',
                    'data' => $stmt->fetchAll()
                ];
            } else {
                return [
                    'type' => 'write',
                    'affected' => $stmt->rowCount()
                ];
            }
        } catch (Exception $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }

    private function createConnection(array $data): int
    {
        $sql = "INSERT INTO cms_db_connections (name, type, host, port, database, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        DB::execute($sql, [
            $data['name'],
            $data['type'],
            $data['host'] ?? null,
            $data['port'] ?? null,
            $data['database'],
            $data['username'] ?? null,
            $data['password'] ?? null
        ]);

        return DB::lastInsertId();
    }

    private function updateConnection(array $data): int
    {
        // Check if password is provided, otherwise keep existing
        if (empty($data['password'])) {
            $sql = "UPDATE cms_db_connections SET name=?, type=?, host=?, port=?, database=?, username=?, updated_at=CURRENT_TIMESTAMP WHERE id=?";
            DB::execute($sql, [
                $data['name'],
                $data['type'],
                $data['host'] ?? null,
                $data['port'] ?? null,
                $data['database'],
                $data['username'] ?? null,
                $data['id']
            ]);
        } else {
            $sql = "UPDATE cms_db_connections SET name=?, type=?, host=?, port=?, database=?, username=?, password=?, updated_at=CURRENT_TIMESTAMP WHERE id=?";
            DB::execute($sql, [
                $data['name'],
                $data['type'],
                $data['host'] ?? null,
                $data['port'] ?? null,
                $data['database'],
                $data['username'] ?? null,
                $data['password'],
                $data['id']
            ]);
        }

        return $data['id'];
    }

    private function getConnectionWithPassword(int $id): ?array
    {
        return DB::fetch("SELECT * FROM cms_db_connections WHERE id = ?", [$id]);
    }

    private function validateConnectionData(array $data): void
    {
        if (empty($data['name']))
            throw new Exception("Name is required");
        if (empty($data['type']))
            throw new Exception("Type is required");
        if (!in_array($data['type'], $this->allowedTypes))
            throw new Exception("Invalid database type");
        if (empty($data['database']))
            throw new Exception("Database name/path is required");

        if ($data['type'] !== 'sqlite') {
            if (empty($data['host']))
                throw new Exception("Host is required");
        }
    }

    private function buildDsn(array $data): string
    {
        if ($data['type'] === 'sqlite') {
            return "sqlite:" . $data['database'];
        }

        $dsn = "{$data['type']}:host={$data['host']};dbname={$data['database']}";
        if (!empty($data['port'])) {
            $dsn .= ";port={$data['port']}";
        }
        return $dsn;
    }
}
