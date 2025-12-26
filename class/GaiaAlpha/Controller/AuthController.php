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

            $response = [
                'success' => true,
                'user' => [
                    'username' => $user['username'],
                    'level' => (int) $user['level']
                ],
                'admin_prefix' => \GaiaAlpha\Router::adminPrefix()
            ];

            // Generate JWT if plugin is available
            if (class_exists('JwtAuth\\Service')) {
                $response['token'] = \JwtAuth\Service::generateToken([
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'level' => (int) $user['level']
                ]);
            }

            Response::json($response);
        } else {
            Response::json(['error' => 'Invalid credentials'], 401);
        }
    }

    public function register()
    {
        $data = Request::input();
        if (empty($data['username']) || empty($data['password'])) {
            Response::json(['error' => 'Missing credentials'], 400);
            return;
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
                ],
                'admin_prefix' => \GaiaAlpha\Router::adminPrefix()
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
        \GaiaAlpha\Router::add('POST', '/@/api/login', [$this, 'login']);
        \GaiaAlpha\Router::add('POST', '/@/api/register', [$this, 'register']);
        \GaiaAlpha\Router::add('POST', '/@/api/logout', [$this, 'logout']);
        \GaiaAlpha\Router::add('GET', '/@/api/user', [$this, 'me']);
    }
}
