<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\Todo;

class TodoController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        $todoModel = new Todo($this->db);
        $todos = $todoModel->findAllByUserId($_SESSION['user_id']);
        $this->jsonResponse($todos);
    }

    public function create()
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (empty($data['title'])) {
            $this->jsonResponse(['error' => 'Title required'], 400);
        }

        $todoModel = new Todo($this->db);
        $id = $todoModel->create($_SESSION['user_id'], $data['title']);

        $this->jsonResponse([
            'id' => $id,
            'title' => $data['title'],
            'completed' => 0
        ]);
    }

    // Since we handle both PATCH and DELETE for specific IDs
    public function update($id)
    {
        $this->requireAuth();
        $data = $this->getJsonInput();
        $todoModel = new Todo($this->db);

        $todoModel->update($id, $_SESSION['user_id'], $data['completed'] ?? false);
        $this->jsonResponse(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAuth();
        $todoModel = new Todo($this->db);
        $todoModel->delete($id, $_SESSION['user_id']);
        $this->jsonResponse(['success' => true]);
    }
}
