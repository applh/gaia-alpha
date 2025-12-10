<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\Todo;

class TodoController extends BaseController
{
    public function index()
    {
        $this->requireAuth();

        // Check if filtering by label
        if (isset($_GET['label'])) {
            $todos = Todo::findByLabel($_SESSION['user_id'], $_GET['label']);
        } else {
            $todos = Todo::findAllByUserId($_SESSION['user_id']);
        }

        $this->jsonResponse($todos);
    }

    public function create()
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (empty($data['title'])) {
            $this->jsonResponse(['error' => 'Title required'], 400);
        }

        $id = Todo::create(
            $_SESSION['user_id'],
            $data['title'],
            $data['parent_id'] ?? null,
            $data['labels'] ?? null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['color'] ?? null
        );

        $newTodo = Todo::find($id, $_SESSION['user_id']);
        $this->jsonResponse($newTodo);
    }

    public function update($id)
    {
        $this->requireAuth();
        $data = $this->getJsonInput();
        Todo::update($id, $_SESSION['user_id'], $data);
        $this->jsonResponse(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAuth();
        Todo::delete($id, $_SESSION['user_id']);
        $this->jsonResponse(['success' => true]);
    }

    public function getChildren($id)
    {
        $this->requireAuth();
        $children = Todo::findChildren($id, $_SESSION['user_id']);
        $this->jsonResponse($children);
    }

    public function reorder()
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (!isset($data['id']) || !isset($data['position'])) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }

        $success = Todo::updatePosition(
            $data['id'],
            $_SESSION['user_id'],
            $data['parent_id'] ?? null,
            (float) $data['position']
        );

        if ($success) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update position'], 500);
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/api/todos', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/api/todos', [$this, 'create']);
        \GaiaAlpha\Router::add('PATCH', '/api/todos/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', '/api/todos/(\d+)', [$this, 'delete']);
        \GaiaAlpha\Router::add('GET', '/api/todos/(\d+)/children', [$this, 'getChildren']);
        \GaiaAlpha\Router::add('POST', '/api/todos/reorder', [$this, 'reorder']);
    }
}
