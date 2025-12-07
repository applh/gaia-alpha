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
        $stmt = $this->db->getPdo()->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
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
            echo json_encode(['success' => true, 'user' => ['username' => $user['username']]]);
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
            echo json_encode(['user' => ['username' => $_SESSION['username']]]);
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
        $stmt = $this->db->getPdo()->prepare("INSERT INTO todos (user_id, title) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $data['title']]);
        echo json_encode(['id' => $this->db->getPdo()->lastInsertId(), 'title' => $data['title'], 'completed' => 0]);
    }

    private function updateTodo($id, $data)
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return;
        }
        $stmt = $this->db->getPdo()->prepare("UPDATE todos SET completed = ? WHERE id = ? AND user_id = ?");
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
}
