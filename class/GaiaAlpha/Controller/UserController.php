<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\User;
use GaiaAlpha\Response;
use GaiaAlpha\Request;

class UserController extends BaseController
{
    public function index()
    {
        if (!$this->requireAdmin())
            return;
        Response::json(User::findAll());
    }

    public function create()
    {
        if (!$this->requireAdmin())
            return;
        $data = Request::input();

        if (empty($data['username']) || empty($data['password'])) {
            Response::json(['error' => 'Missing username or password'], 400);
            return;
        }

        try {
            $id = User::create($data['username'], $data['password'], $data['level'] ?? 10);
            Response::json(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Username already exists'], 400);
            return;
        }
    }

    public function update($id)
    {
        if (!$this->requireAdmin())
            return;
        $data = Request::input();
        User::update($id, $data);
        Response::json(['success' => true]);
    }

    public function delete($id)
    {
        if (!$this->requireAdmin())
            return;
        if ($id == $_SESSION['user_id']) {
            Response::json(['error' => 'Cannot delete yourself'], 400);
            return;
        }

        User::delete($id);
        Response::json(['success' => true]);
    }

    public function settings()
    {
        if (!isset($_SESSION['user_id'])) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = Request::input();
        $key = $data['key'] ?? null;
        $value = $data['value'] ?? null;

        if (!$key) {
            Response::json(['error' => 'Missing key'], 400);
            return;
        }

        // Use DataStore for user preferences
        \GaiaAlpha\Model\DataStore::set($_SESSION['user_id'], 'user_pref', $key, $value);

        Response::json(['success' => true]);
    }

    public function registerRoutes()
    {
        $prefix = \GaiaAlpha\Router::adminPrefix();
        \GaiaAlpha\Router::add('GET', $prefix . '/users', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', $prefix . '/users', [$this, 'create']);
        \GaiaAlpha\Router::add('PATCH', $prefix . '/users/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', $prefix . '/users/(\d+)', [$this, 'delete']);

        // Authenticated user settings
        \GaiaAlpha\Router::add('POST', '/@/api/user/settings', [$this, 'settings']);
    }
}
