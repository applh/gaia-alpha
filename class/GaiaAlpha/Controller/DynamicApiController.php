<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Env;
use GaiaAlpha\Router;
use GaiaAlpha\Database;
use \PDO;

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

            $prefix = "/api/v1/$table";

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
        if (file_exists($path)) {
            $content = file_get_contents($path);
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
            $this->requireAuth();
            return;
        }

        // Default to admin
        $this->requireAdmin();
    }

    public function handleList(string $table)
    {
        $this->checkAccess($table);
        $db = DbController::connect();
        $pdo = $db->getPdo();

        // Basic Pagination
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;

        // Basic Sorting
        $sort = $_GET['sort'] ?? 'id';
        $order = strtolower($_GET['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

        // Sanitize sort column to prevent injection
        $sort = preg_replace('/[^a-zA-Z0-9_]/', '', $sort);

        $sql = "SELECT * FROM $table ORDER BY $sort $order LIMIT $limit OFFSET $offset";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Count total
        $countParams = "SELECT COUNT(*) FROM $table";
        $total = $pdo->query($countParams)->fetchColumn();

        $this->jsonResponse([
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
        $db = DbController::connect();
        $pdo = $db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Not Found']);
            return;
        }

        $this->jsonResponse($row);
    }

    public function handleCreate(string $table)
    {
        $this->checkAccess($table);
        $data = $this->getJsonInput();

        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'No data provided']);
            return;
        }

        $db = DbController::connect();
        $pdo = $db->getPdo();

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            $id = $pdo->lastInsertId();

            http_response_code(201);
            $this->jsonResponse(['id' => $id, 'message' => 'Created successfully']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function handleUpdate(string $table, string $id)
    {
        $this->checkAccess($table);
        $data = $this->getJsonInput();

        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'No data provided']);
            return;
        }

        $db = DbController::connect();
        $pdo = $db->getPdo();

        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = "$col = ?";
        }
        $setString = implode(', ', $sets);

        $sql = "UPDATE $table SET $setString WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $this->jsonResponse(['message' => 'Updated successfully']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function handleDelete(string $table, string $id)
    {
        $this->checkAccess($table);
        $db = DbController::connect();
        $pdo = $db->getPdo();

        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);

        $this->jsonResponse(['message' => 'Deleted successfully']);
    }
}
