<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\Todo;

class TodoController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        $todoModel = new Todo($this->db);

        // Check if filtering by label
        if (isset($_GET['label'])) {
            $todos = $todoModel->findByLabel($_SESSION['user_id'], $_GET['label']);
        } else {
            $todos = $todoModel->findAllByUserId($_SESSION['user_id']);
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

        $todoModel = new Todo($this->db);
        $id = $todoModel->create(
            $_SESSION['user_id'],
            $data['title'],
            $data['parent_id'] ?? null,
            $data['labels'] ?? null
        );

        $this->jsonResponse([
            'id' => $id,
            'title' => $data['title'],
            'completed' => 0,
            'parent_id' => $data['parent_id'] ?? null,
            'labels' => $data['labels'] ?? null
        ]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $data = $this->getJsonInput();
        $todoModel = new Todo($this->db);

        $todoModel->update($id, $_SESSION['user_id'], $data);
        $this->jsonResponse(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAuth();
        $todoModel = new Todo($this->db);
        $todoModel->delete($id, $_SESSION['user_id']);
        $this->jsonResponse(['success' => true]);
    }

    public function getChildren($id)
    {
        $this->requireAuth();
        $todoModel = new Todo($this->db);
        $children = $todoModel->findChildren($id, $_SESSION['user_id']);
        $this->jsonResponse($children);
    }
}
