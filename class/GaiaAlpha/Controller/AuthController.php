<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\User;

class AuthController extends BaseController
{
    public function init()
    {
        // Skip session for asset requests (performance + header fix)
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (strpos($uri, '/min/') === 0 || strpos($uri, '/assets/') === 0) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login()
    {
        $data = $this->getJsonInput();
        $user = User::findByUsername($data['username'] ?? '');

        if ($user && password_verify($data['password'] ?? '', $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['level'] = (int) $user['level'];

            $this->jsonResponse([
                'success' => true,
                'user' => [
                    'username' => $user['username'],
                    'level' => (int) $user['level']
                ]
            ]);
        } else {
            $this->jsonResponse(['error' => 'Invalid credentials'], 401);
        }
    }

    public function register()
    {
        $data = $this->getJsonInput();
        if (empty($data['username']) || empty($data['password'])) {
            $this->jsonResponse(['error' => 'Missing credentials'], 400);
        }

        try {
            User::create($data['username'], $data['password']);
            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
    }

    public function logout()
    {
        session_destroy();
        $this->jsonResponse(['success' => true]);
    }

    public function me()
    {
        if (isset($_SESSION['user_id'])) {
            $this->jsonResponse([
                'user' => [
                    'username' => $_SESSION['username'],
                    'level' => $_SESSION['level'] ?? 10
                ]
            ]);
        } else {
            $this->jsonResponse(['user' => null]);
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('POST', '/api/login', [$this, 'login']);
        \GaiaAlpha\Router::add('POST', '/api/register', [$this, 'register']);
        \GaiaAlpha\Router::add('POST', '/api/logout', [$this, 'logout']);
        \GaiaAlpha\Router::add('GET', '/api/user', [$this, 'me']);
    }
}
