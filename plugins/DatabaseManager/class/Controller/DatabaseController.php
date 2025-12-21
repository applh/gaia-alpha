<?php

namespace DatabaseManager\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Model\DB;
use GaiaAlpha\Router;
use GaiaAlpha\Request;
use GaiaAlpha\Response;

class DatabaseController extends BaseController
{
    public function getTables()
    {
        error_log("DatabaseController::getTables called");
        if (!$this->requireAdmin()) {
            error_log("DatabaseController::getTables - Auth Failed");
            return;
        }
        try {
            $tables = DB::getTables();
            error_log("DatabaseController::getTables - Found " . count($tables) . " tables");
            Response::json(['tables' => $tables]);
        } catch (\Exception $e) {
            error_log("DatabaseController::getTables - Error: " . $e->getMessage());
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getTableData($tableName)
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            Response::json(['error' => 'Invalid table name'], 400);
            return;
        }

        $schema = DB::getTableSchema($tableName);

        $data = DB::getTableRecords($tableName);
        $count = DB::getTableCount($tableName);

        Response::json([
            'table' => $tableName,
            'schema' => $schema,
            'data' => $data,
            'count' => $count
        ]);
    }

    public function executeQuery()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        $data = Request::input();

        if (empty($data['query'])) {
            Response::json(['error' => 'No query provided'], 400);
            return;
        }

        $query = trim($data['query']);

        try {
            $isSelect = stripos($query, 'SELECT') === 0;

            if ($isSelect) {
                $stmt = DB::query($query);
                $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                Response::json([
                    'success' => true,
                    'type' => 'select',
                    'results' => $results,
                    'count' => count($results)
                ]);
            } else {
                $stmt = DB::query($query);
                $affectedRows = $stmt->rowCount();
                Response::json([
                    'success' => true,
                    'type' => 'modification',
                    'affected_rows' => $affectedRows
                ]);
            }
        } catch (\PDOException $e) {
            Response::json([
                'error' => 'Query execution failed: ' . $e->getMessage()
            ], 400);
        }
    }

    public function createRecord($tableName)
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            Response::json(['error' => 'Invalid table name'], 400);
            return;
        }

        $data = Request::input();

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

            Response::json([
                'success' => true,
                'id' => DB::lastInsertId()
            ]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Insert failed: ' . $e->getMessage()], 400);
        }
    }

    public function updateRecord($tableName, $id)
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            Response::json(['error' => 'Invalid table name'], 400);
            return;
        }

        $data = Request::input();

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

            Response::json(['success' => true]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Update failed: ' . $e->getMessage()], 400);
        }
    }

    public function deleteRecord($tableName, $id)
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            Response::json(['error' => 'Invalid table name'], 400);
            return;
        }

        try {
            DB::execute("DELETE FROM $tableName WHERE id = ?", [$id]);

            Response::json(['success' => true]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Delete failed: ' . $e->getMessage()], 400);
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
