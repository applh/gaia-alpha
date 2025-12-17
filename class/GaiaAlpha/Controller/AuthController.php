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

        \GaiaAlpha\Session::start();
    }

    public function login()
    {
        $data = $this->getJsonInput();
        $user = User::findByUsername($data['username'] ?? '');

        if ($user && password_verify($data['password'] ?? '', $user['password_hash'])) {
            \GaiaAlpha\Session::login($user);

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
        \GaiaAlpha\Session::logout();
        $this->jsonResponse(['success' => true]);
    }

    public function me()
    {
        if (\GaiaAlpha\Session::isLoggedIn()) {
            $data = [
                'user' => [
                    'username' => \GaiaAlpha\Session::get('username'),
                    'level' => \GaiaAlpha\Session::level()
                ]
            ];

            // Allow plugins to inject data (e.g. menu items)
            $data = \GaiaAlpha\Hook::filter('auth_session_data', $data);

            $this->jsonResponse($data);
        } else {
            $this->jsonResponse(['user' => null]);
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('POST', '/@/login', [$this, 'login']);

        \GaiaAlpha\Router::add('POST', '/@/register', [$this, 'register']);
        \GaiaAlpha\Router::add('POST', '/@/logout', [$this, 'logout']);
        \GaiaAlpha\Router::add('GET', '/@/user', [$this, 'me']);
    }
}
