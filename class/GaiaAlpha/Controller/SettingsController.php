<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Database;
use GaiaAlpha\Model\DataStore;
use GaiaAlpha\Controller\DbController;

class SettingsController
{
    private Database $db;

    public function __construct()
    {
        $this->db = DbController::connect();
    }

    private function requireAuth()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    private function jsonResponse($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function index()
    {
        $this->requireAuth();
        $model = new DataStore($this->db);

        // We currently only store user preferences under type 'user_pref'
        $settings = $model->getAll($_SESSION['user_id'], 'user_pref');

        $this->jsonResponse(['settings' => $settings]);
    }

    public function update()
    {
        $this->requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['key']) || !isset($data['value'])) {
            $this->jsonResponse(['error' => 'Missing key or value'], 400);
        }

        $model = new DataStore($this->db);
        $success = $model->set($_SESSION['user_id'], 'user_pref', $data['key'], $data['value']);

        if ($success) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update setting'], 500);
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/api/settings', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/api/settings', [$this, 'update']);
    }
}
