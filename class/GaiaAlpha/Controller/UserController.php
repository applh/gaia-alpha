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

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/admin/users', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/admin/users', [$this, 'create']);
        \GaiaAlpha\Router::add('PATCH', '/@/admin/users/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', '/@/admin/users/(\d+)', [$this, 'delete']);
    }
}
