<?php

namespace ApiBuilder\Controller;

use GaiaAlpha\File;
use GaiaAlpha\Env;
use GaiaAlpha\Router;
use GaiaAlpha\Database;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use \PDO;
use GaiaAlpha\Controller\BaseController;

class DynamicApiController extends BaseController
{
    private array $config = [];

    public function registerRoutes()
    {
        $this->loadConfig();

        foreach ($this->config as $table => $settings) {
            if (empty($settings['enabled'])) {
                continue;
            }

            // The user's instruction was to change API_BASE_PATH constant to use /@/.
            // There was no existing API_BASE_PATH constant.
            // The provided snippet was syntactically incorrect for a constant definition within a loop.
            // Assuming the intent was to change the base path for the API routes
            // and potentially introduce a constant for it, the most direct interpretation
            // that results in syntactically correct PHP is to modify the `$prefix` variable.
            // If a class constant was intended, it would need to be defined outside this method.
            // For now, we'll modify the `$prefix` variable directly as it's the closest
            // functional equivalent to the spirit of changing the base path.
            $prefix = "/@/v1/$table";

            // LIST
            if (in_array('GET', $settings['methods'])) {
                Router::add('GET', $prefix, function () use ($table) {
                    $this->handleList($table);
                });

                // GET ONE
                Router::add('GET', "$prefix/(\d+)", function ($id) use ($table) {
                    $this->handleGet($table, $id);
                });
            }

            // CREATE
            if (in_array('POST', $settings['methods'])) {
                Router::add('POST', $prefix, function () use ($table) {
                    $this->handleCreate($table);
                });
            }

            // UPDATE
            if (in_array('PUT', $settings['methods'])) {
                Router::add('PUT', "$prefix/(\d+)", function ($id) use ($table) {
                    $this->handleUpdate($table, $id);
                });
            }

            // DELETE
            if (in_array('DELETE', $settings['methods'])) {
                Router::add('DELETE', "$prefix/(\d+)", function ($id) use ($table) {
                    $this->handleDelete($table, $id);
                });
            }
        }
    }

    private function loadConfig()
    {
        $path = Env::get('path_data') . '/api-config.json';
        $content = File::read($path);
        if ($content !== false) {
            $this->config = json_decode($content, true) ?? [];
        }
    }

    private function checkAccess(string $table)
    {
        $level = $this->config[$table]['auth_level'] ?? 'admin';

        if ($level === 'public') {
            return;
        }

        if ($level === 'user') {
            if (!$this->requireAuth())
                return;
            return;
        }

        // Default to admin
        $this->requireAdmin();
    }

    public function handleList(string $table)
    {
        $this->checkAccess($table);
        // Basic Pagination
        $page = Request::queryInt('page', 1);
        $limit = Request::queryInt('limit', 20);
        $offset = ($page - 1) * $limit;

        // Basic Sorting
        $sort = Request::query('sort', 'id');
        $order = strtolower(Request::query('order', 'asc')) === 'desc' ? 'DESC' : 'ASC';

        // Sanitize sort column to prevent injection
        $sort = preg_replace('/[^a-zA-Z0-9_]/', '', $sort);

        $sql = "SELECT * FROM $table ORDER BY $sort $order LIMIT $limit OFFSET $offset";
        $rows = \GaiaAlpha\Model\DB::fetchAll($sql);

        // Count total
        $countParams = "SELECT COUNT(*) FROM $table";
        $total = \GaiaAlpha\Model\DB::fetchColumn($countParams);

        Response::json([
            'data' => $rows,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    public function handleGet(string $table, string $id)
    {
        $this->checkAccess($table);
        $row = \GaiaAlpha\Model\DB::fetch("SELECT * FROM $table WHERE id = ?", [$id]);

        if (!$row) {
            Response::json(['error' => 'Not Found'], 404);
            return;
        }

        Response::json($row);
    }

    public function handleCreate(string $table)
    {
        $this->checkAccess($table);
        $data = Request::input();

        if (empty($data)) {
            Response::json(['error' => 'No data provided'], 400);
            return;
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        try {
            \GaiaAlpha\Model\DB::execute($sql, array_values($data));
            $id = \GaiaAlpha\Model\DB::lastInsertId();

            Response::json(['id' => $id, 'message' => 'Created successfully'], 201);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleUpdate(string $table, string $id)
    {
        $this->checkAccess($table);
        $data = Request::input();

        if (empty($data)) {
            Response::json(['error' => 'No data provided'], 400);
            return;
        }

        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = "$col = ?";
        }
        $setString = implode(', ', $sets);

        $sql = "UPDATE $table SET $setString WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;

        try {
            \GaiaAlpha\Model\DB::execute($sql, $values);
            Response::json(['message' => 'Updated successfully']);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleDelete(string $table, string $id)
    {
        $this->checkAccess($table);
        try {
            \GaiaAlpha\Model\DB::execute("DELETE FROM $table WHERE id = ?", [$id]);
            Response::json(['message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
