<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\User;
use GaiaAlpha\Model\Todo;
use GaiaAlpha\Model\Page;

class AdminController extends BaseController
{
    public function index()
    {
        $this->requireAdmin();
        $userModel = new User($this->db);
        $this->jsonResponse($userModel->findAll());
    }

    public function stats()
    {
        $this->requireAdmin();
        $userModel = new User($this->db);
        $todoModel = new Todo($this->db);
        $pageModel = new Page($this->db);

        $this->jsonResponse([
            'users' => $userModel->count(),
            'todos' => $todoModel->count(),
            'pages' => $pageModel->count('page'),
            'images' => $pageModel->count('image'),
            'datastore' => $this->db->getPdo()->query("SELECT COUNT(*) FROM data_store")->fetchColumn()
        ]);
    }

    public function create()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();

        if (empty($data['username']) || empty($data['password'])) {
            $this->jsonResponse(['error' => 'Missing username or password'], 400);
        }

        $userModel = new User($this->db);
        try {
            $id = $userModel->create($data['username'], $data['password'], $data['level'] ?? 10);
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
    }

    public function update($id)
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();
        $userModel = new User($this->db);

        $userModel->update($id, $data);
        $this->jsonResponse(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAdmin();
        if ($id == $_SESSION['user_id']) {
            $this->jsonResponse(['error' => 'Cannot delete yourself'], 400);
        }

        $userModel = new User($this->db);
        $userModel->delete($id);
        $this->jsonResponse(['success' => true]);
    }

    // Database Management Endpoints

    public function getTables()
    {
        $this->requireAdmin();
        $pdo = $this->db->getPdo();

        // Get all tables from SQLite
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $this->jsonResponse(['tables' => $tables]);
    }

    public function getTableData($tableName)
    {
        $this->requireAdmin();

        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            $this->jsonResponse(['error' => 'Invalid table name'], 400);
            return;
        }

        $pdo = $this->db->getPdo();

        // Get table schema
        $schemaStmt = $pdo->query("PRAGMA table_info($tableName)");
        $schema = $schemaStmt->fetchAll();

        // Get table data
        $dataStmt = $pdo->query("SELECT * FROM $tableName LIMIT 100");
        $data = $dataStmt->fetchAll();

        $this->jsonResponse([
            'table' => $tableName,
            'schema' => $schema,
            'data' => $data,
            'count' => count($data)
        ]);
    }

    public function executeQuery()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();

        if (empty($data['query'])) {
            $this->jsonResponse(['error' => 'No query provided'], 400);
            return;
        }

        $query = trim($data['query']);
        $pdo = $this->db->getPdo();

        try {
            // Determine if it's a SELECT query or a modification query
            $isSelect = stripos($query, 'SELECT') === 0;

            if ($isSelect) {
                $stmt = $pdo->query($query);
                $results = $stmt->fetchAll();
                $this->jsonResponse([
                    'success' => true,
                    'type' => 'select',
                    'results' => $results,
                    'count' => count($results)
                ]);
            } else {
                $affectedRows = $pdo->exec($query);
                $this->jsonResponse([
                    'success' => true,
                    'type' => 'modification',
                    'affected_rows' => $affectedRows
                ]);
            }
        } catch (\PDOException $e) {
            $this->jsonResponse([
                'error' => 'Query execution failed: ' . $e->getMessage()
            ], 400);
        }
    }

    public function createRecord($tableName)
    {
        $this->requireAdmin();

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            $this->jsonResponse(['error' => 'Invalid table name'], 400);
            return;
        }

        $data = $this->getJsonInput();
        $pdo = $this->db->getPdo();

        try {
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $tableName,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));

            $this->jsonResponse([
                'success' => true,
                'id' => $pdo->lastInsertId()
            ]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Insert failed: ' . $e->getMessage()], 400);
        }
    }

    public function updateRecord($tableName, $id)
    {
        $this->requireAdmin();

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            $this->jsonResponse(['error' => 'Invalid table name'], 400);
            return;
        }

        $data = $this->getJsonInput();
        $pdo = $this->db->getPdo();

        try {
            $setParts = [];
            $values = [];

            foreach ($data as $column => $value) {
                $setParts[] = "$column = ?";
                $values[] = $value;
            }
            $values[] = $id;

            $sql = sprintf(
                "UPDATE %s SET %s WHERE id = ?",
                $tableName,
                implode(', ', $setParts)
            );

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Update failed: ' . $e->getMessage()], 400);
        }
    }

    public function deleteRecord($tableName, $id)
    {
        $this->requireAdmin();

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            $this->jsonResponse(['error' => 'Invalid table name'], 400);
            return;
        }

        $pdo = $this->db->getPdo();

        try {
            $stmt = $pdo->prepare("DELETE FROM $tableName WHERE id = ?");
            $stmt->execute([$id]);

            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Delete failed: ' . $e->getMessage()], 400);
        }
    }
}
