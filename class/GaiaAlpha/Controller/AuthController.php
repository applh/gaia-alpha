<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\User;
use GaiaAlpha\Response;
use GaiaAlpha\Request;

class AuthController extends BaseController
{
    public function init()
    {
        // Skip session for asset requests (performance + header fix)
        $uri = Request::uri();
        if (str_starts_with($uri, '/min/') || str_starts_with($uri, '/assets/')) {
            return;
        }

        \GaiaAlpha\Session::start();
    }

    public function login()
    {
        $data = Request::input();
        $user = User::findByUsername($data['username'] ?? '');

        if ($user && password_verify($data['password'] ?? '', $user['password_hash'])) {
            \GaiaAlpha\Session::login($user);

            Response::json([
                'success' => true,
                'user' => [
                    'username' => $user['username'],
                    'level' => (int) $user['level']
                ]
            ]);
        } else {
            Response::json(['error' => 'Invalid credentials'], 401);
        }
    }

    public function register()
    {
        $data = Request::input();
        if (empty($data['username']) || empty($data['password'])) {
            Response::json(['error' => 'Missing credentials'], 400);
        }

        try {
            User::create($data['username'], $data['password']);
            Response::json(['success' => true]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Username already exists'], 400);
        }
    }

    public function logout()
    {
        \GaiaAlpha\Session::logout();
        Response::json(['success' => true]);
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

            Response::json($data);
        } else {
            Response::json(['user' => null]);
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
