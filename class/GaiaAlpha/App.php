<?php

namespace GaiaAlpha;

class App
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database(__DIR__ . '/../../database.sqlite');
        $this->db->ensureSchema();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (strpos($uri, '/api/') === 0) {
            $this->handleApi($uri);
        } else {
            include dirname(__DIR__, 2) . '/templates/home.php';
        }
    }

    private function handleApi(string $uri)
    {
        header('Content-Type: application/json');
        $method = $_SERVER['REQUEST_METHOD'];
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            if ($uri === '/api/login' && $method === 'POST') {
                $this->login($input);
            } elseif ($uri === '/api/register' && $method === 'POST') {
                $this->register($input);
            } elseif ($uri === '/api/logout' && $method === 'POST') {
                $this->logout();
            } elseif ($uri === '/api/user' && $method === 'GET') {
                $this->getCurrentUser();
            } elseif ($uri === '/api/todos' && $method === 'GET') {
                $this->getTodos();
            } elseif ($uri === '/api/todos' && $method === 'POST') {
                $this->addTodo($input);
            } elseif (preg_match('#^/api/todos/(\d+)$#', $uri, $matches)) {
                $id = (int) $matches[1];
                if ($method === 'PATCH') {
                    $this->updateTodo($id, $input);
                } elseif ($method === 'DELETE') {
                    $this->deleteTodo($id);
                }
            } elseif ($uri === '/api/admin/users' && $method === 'GET') {
                $this->getAdminUsers();
            } elseif ($uri === '/api/admin/users' && $method === 'POST') {
                $this->handleAdminCreateUser($input);
            } elseif (preg_match('#^/api/admin/users/(\d+)$#', $uri, $matches)) {
                $id = (int) $matches[1];
                if ($method === 'PATCH') {
                    $this->handleAdminUpdateUser($id, $input);
                } elseif ($method === 'DELETE') {
                    $this->handleAdminDeleteUser($id);
                }
            } elseif ($uri === '/api/admin/stats' && $method === 'GET') {
                $this->getAdminStats();
            } elseif ($uri === '/api/cms/pages' && $method === 'GET') {
                $this->getCmsPages();
            } elseif ($uri === '/api/cms/pages' && $method === 'POST') {
                $this->addCmsPage($input);
            } elseif (preg_match('#^/api/cms/pages/(\d+)$#', $uri, $matches)) {
                $id = (int) $matches[1];
                if ($method === 'PATCH') {
                    $this->updateCmsPage($id, $input);
                } elseif ($method === 'DELETE') {
                    $this->deleteCmsPage($id);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Not Found']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function register($data)
    {
        if (empty($data['username']) || empty($data['password'])) {
            throw new \Exception('Missing credentials');
        }
        $stmt = $this->db->getPdo()->prepare("INSERT INTO users (username, password_hash, level, created_at, updated_at) VALUES (?, ?, 10, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        try {
            $stmt->execute([$data['username'], password_hash($data['password'], PASSWORD_DEFAULT)]);
            echo json_encode(['success' => true]);
        } catch (\PDOException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Username already exists']);
        }
    }

    private function login($data)
    {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        $user = $stmt->fetch();

        if ($user && password_verify($data['password'], $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['level'] = (int) $user['level'];
            echo json_encode([
                'success' => true,
                'user' => [
                    'username' => $user['username'],
                    'level' => (int) $user['level']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    }

    private function logout()
    {
        session_destroy();
        echo json_encode(['success' => true]);
    }

    private function getCurrentUser()
    {
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'user' => [
                    'username' => $_SESSION['username'],
                    'level' => $_SESSION['level'] ?? 10
                ]
            ]);
        } else {
            echo json_encode(['user' => null]);
        }
    }

    private function getTodos()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return;
        }
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM todos WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode($stmt->fetchAll());
    }

    private function addTodo($data)
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return;
        }
        $stmt = $this->db->getPdo()->prepare("INSERT INTO todos (user_id, title, created_at, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([$_SESSION['user_id'], $data['title']]);
        echo json_encode(['id' => $this->db->getPdo()->lastInsertId(), 'title' => $data['title'], 'completed' => 0]);
    }

    private function updateTodo($id, $data)
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return;
        }
        $stmt = $this->db->getPdo()->prepare("UPDATE todos SET completed = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['completed'] ? 1 : 0, $id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    }

    private function deleteTodo($id)
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return;
        }
        $stmt = $this->db->getPdo()->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    }

    private function getAdminUsers()
    {
        if (!isset($_SESSION['level']) || $_SESSION['level'] < 100) {
            http_response_code(403);
            return;
        }
        $stmt = $this->db->getPdo()->query("SELECT id, username, level, created_at, updated_at FROM users ORDER BY id DESC");
        echo json_encode($stmt->fetchAll());
    }

    private function getAdminStats()
    {
        if (!isset($_SESSION['level']) || $_SESSION['level'] < 100) {
            http_response_code(403);
            return;
        }
        $userCount = $this->db->getPdo()->query("SELECT count(*) FROM users")->fetchColumn();
        $todoCount = $this->db->getPdo()->query("SELECT count(*) FROM todos")->fetchColumn();
        echo json_encode(['users' => $userCount, 'todos' => $todoCount]);
    }

    private function handleAdminCreateUser($data)
    {
        if (!isset($_SESSION['level']) || $_SESSION['level'] < 100) {
            http_response_code(403);
            return;
        }
        if (empty($data['username']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing username or password']);
            return;
        }
        $level = isset($data['level']) ? (int) $data['level'] : 10;

        $stmt = $this->db->getPdo()->prepare("INSERT INTO users (username, password_hash, level, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        try {
            $stmt->execute([$data['username'], password_hash($data['password'], PASSWORD_DEFAULT), $level]);
            echo json_encode(['success' => true, 'id' => $this->db->getPdo()->lastInsertId()]);
        } catch (\PDOException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Username already exists']);
        }
    }

    private function handleAdminUpdateUser($id, $data)
    {
        if (!isset($_SESSION['level']) || $_SESSION['level'] < 100) {
            http_response_code(403);
            return;
        }

        // Build query efficiently
        $fields = [];
        $values = [];

        if (isset($data['level'])) {
            $fields[] = "level = ?";
            $values[] = (int) $data['level'];
        }

        if (!empty($data['password'])) {
            $fields[] = "password_hash = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            echo json_encode(['success' => true, 'message' => 'No changes made']);
            return;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $values[] = $id;

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($values);

        echo json_encode(['success' => true]);
    }

    private function handleAdminDeleteUser($id)
    {
        if (!isset($_SESSION['level']) || $_SESSION['level'] < 100) {
            http_response_code(403);
            return;
        }

        // Prevent deleting yourself
        if ($id == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete yourself']);
            return;
        }

        $stmt = $this->db->getPdo()->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);


        echo json_encode(['success' => true]);
    }

    private function getCmsPages()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return;
        }
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM cms_pages WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode($stmt->fetchAll());
    }

    private function addCmsPage($data)
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return;
        }
        if (empty($data['title']) || empty($data['slug'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing title or slug']);
            return;
        }

        $stmt = $this->db->getPdo()->prepare("INSERT INTO cms_pages (user_id, title, slug, content, created_at, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        try {
            $stmt->execute([$_SESSION['user_id'], $data['title'], $data['slug'], $data['content'] ?? '']);
            echo json_encode(['success' => true, 'id' => $this->db->getPdo()->lastInsertId()]);
        } catch (\PDOException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Slug already exists']);
        }
    }

    private function updateCmsPage($id, $data)
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return;
        }

        $fields = [];
        $values = [];

        if (isset($data['title'])) {
            $fields[] = "title = ?";
            $values[] = $data['title'];
        }
        if (isset($data['content'])) {
            $fields[] = "content = ?";
            $values[] = $data['content'];
        }

        if (empty($fields)) {
            echo json_encode(['success' => true, 'message' => 'No changes made']);
            return;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $sql = "UPDATE cms_pages SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $values[] = $id;
        $values[] = $_SESSION['user_id'];

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($values);

        echo json_encode(['success' => true]);
    }

    private function deleteCmsPage($id)
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return;
        }
        $stmt = $this->db->getPdo()->prepare("DELETE FROM cms_pages WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    }
}
