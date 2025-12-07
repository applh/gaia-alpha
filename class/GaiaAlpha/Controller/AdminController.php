<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\User;
use GaiaAlpha\Model\Todo;
use GaiaAlpha\Model\Page;

class AdminController extends BaseController
{
    public function index()
    {
        $this->requireAdmin();
        $userModel = new User($this->db);
        $this->jsonResponse($userModel->findAll());
    }

    public function stats()
    {
        $this->requireAdmin();
        $userModel = new User($this->db);
        $todoModel = new Todo($this->db);
        $pageModel = new Page($this->db);

        $this->jsonResponse([
            'users' => $userModel->count(),
            'todos' => $todoModel->count(),
            'pages' => $pageModel->count('page'),
            'images' => $pageModel->count('image')
        ]);
    }

    public function create()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();

        if (empty($data['username']) || empty($data['password'])) {
            $this->jsonResponse(['error' => 'Missing username or password'], 400);
        }

        $userModel = new User($this->db);
        try {
            $id = $userModel->create($data['username'], $data['password'], $data['level'] ?? 10);
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
    }

    public function update($id)
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();
        $userModel = new User($this->db);

        $userModel->update($id, $data);
        $this->jsonResponse(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAdmin();
        if ($id == $_SESSION['user_id']) {
            $this->jsonResponse(['error' => 'Cannot delete yourself'], 400);
        }

        $userModel = new User($this->db);
        $userModel->delete($id);
        $this->jsonResponse(['success' => true]);
    }
}
