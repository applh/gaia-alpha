<?php

namespace Todo\Controller;

use GaiaAlpha\Controller\BaseController;
use Todo\Model\Todo;
use GaiaAlpha\Response;
use GaiaAlpha\Request;

class TodoController extends BaseController
{
    public function index()
    {
        $this->requireAuth();

        // Check if filtering by label
        $label = Request::query('label');
        if ($label) {
            $todos = Todo::findByLabel(\GaiaAlpha\Session::id(), $label);
        } else {
            $todos = Todo::findAllByUserId(\GaiaAlpha\Session::id());
        }

        Response::json($todos);
    }

    public function create()
    {
        $this->requireAuth();
        $data = Request::input();

        if (empty($data['title'])) {
            Response::json(['error' => 'Title required'], 400);
            return;
        }

        $id = Todo::create(
            \GaiaAlpha\Session::id(),
            $data['title'],
            $data['parent_id'] ?? null,
            $data['labels'] ?? null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['color'] ?? null
        );

        $newTodo = Todo::find($id, \GaiaAlpha\Session::id());
        Response::json($newTodo);
    }

    public function update($id)
    {
        $this->requireAuth();
        $data = Request::input();
        Todo::update($id, \GaiaAlpha\Session::id(), $data);
        Response::json(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAuth();
        Todo::delete($id, \GaiaAlpha\Session::id());
        Response::json(['success' => true]);
    }

    public function getChildren($id)
    {
        $this->requireAuth();
        $children = Todo::findChildren($id, \GaiaAlpha\Session::id());
        Response::json($children);
    }

    public function reorder()
    {
        $this->requireAuth();
        $data = Request::input();

        if (!isset($data['id']) || !isset($data['position'])) {
            Response::json(['error' => 'Missing required fields'], 400);
            return;
        }

        $success = Todo::updatePosition(
            $data['id'],
            \GaiaAlpha\Session::id(),
            $data['parent_id'] ?? null,
            (float) $data['position']
        );


        if ($success) {
            Response::json(['success' => true]);
        } else {
            Response::json(['error' => 'Failed to update position'], 500);
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/todos', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/todos', [$this, 'create']);
        \GaiaAlpha\Router::add('PATCH', '/@/todos/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', '/@/todos/(\d+)', [$this, 'delete']);
        \GaiaAlpha\Router::add('GET', '/@/todos/(\d+)/children', [$this, 'getChildren']);
        \GaiaAlpha\Router::add('POST', '/@/todos/reorder', [$this, 'reorder']);
    }
}
