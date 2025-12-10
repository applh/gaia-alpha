<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Database;

use GaiaAlpha\Controller\DbController;

abstract class BaseController
{
    protected Database $db;

    public function __construct()
    {
        $this->db = DbController::connect();
    }

    protected function jsonResponse($data, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function getJsonInput()
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    protected function requireAuth()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
    }

    protected function requireAdmin()
    {
        $this->requireAuth();
        if (!isset($_SESSION['level']) || $_SESSION['level'] < 100) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
        }
    }

    public function registerRoutes()
    {
        // Override in subclasses
    }

    public function init()
    {
        // Override in subclasses
    }

    public function getRank()
    {
        return 10;
    }
}
