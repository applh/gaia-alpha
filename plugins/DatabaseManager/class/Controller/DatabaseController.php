<?php

namespace DatabaseManager\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Model\DB;
use GaiaAlpha\Router;

class DatabaseController extends BaseController
{
    public function getTables()
    {
        $this->requireAdmin();
        $tables = DB::getTables();


        $this->jsonResponse(['tables' => $tables]);
    }

    public function getTableData($tableName)
    {
        $this->requireAdmin();

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            $this->jsonResponse(['error' => 'Invalid table name'], 400);
            return;
        }

        $schema = DB::getTableSchema($tableName);

        $data = DB::getTableRecords($tableName);
        $count = DB::getTableCount($tableName);

        $this->jsonResponse([
            'table' => $tableName,
            'schema' => $schema,
            'data' => $data,
            'count' => $count
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

        try {
            $isSelect = stripos($query, 'SELECT') === 0;

            if ($isSelect) {
                $stmt = DB::query($query);
                $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $this->jsonResponse([
                    'success' => true,
                    'type' => 'select',
                    'results' => $results,
                    'count' => count($results)
                ]);
            } else {
                $stmt = DB::query($query);
                $affectedRows = $stmt->rowCount();
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

        try {
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $tableName,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            DB::execute($sql, array_values($data));

            $this->jsonResponse([
                'success' => true,
                'id' => DB::lastInsertId()
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

            DB::execute($sql, $values);

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

        try {
            DB::execute("DELETE FROM $tableName WHERE id = ?", [$id]);

            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Delete failed: ' . $e->getMessage()], 400);
        }
    }

    public function registerRoutes()
    {
        Router::add('GET', '/@/admin/db/tables', [$this, 'getTables']);
        Router::add('GET', '/@/admin/db/table/(\w+)', [$this, 'getTableData']);
        Router::add('POST', '/@/admin/db/query', [$this, 'executeQuery']);
        Router::add('POST', '/@/admin/db/table/(\w+)', [$this, 'createRecord']);
        Router::add('PATCH', '/@/admin/db/table/(\w+)/(\d+)', [$this, 'updateRecord']);
        Router::add('DELETE', '/@/admin/db/table/(\w+)/(\d+)', [$this, 'deleteRecord']);
    }
}
