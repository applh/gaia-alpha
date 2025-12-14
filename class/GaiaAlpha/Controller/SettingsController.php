<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Database;
use GaiaAlpha\Response;
use GaiaAlpha\Model\DataStore;
use GaiaAlpha\Controller\DbController;

class SettingsController
{
    private function requireAuth()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    private function jsonResponse($data, $code = 200)
    {
        Response::json($data, $code);
    }

    public function index()
    {
        $this->requireAuth();

        // We currently only store user preferences under type 'user_pref'
        $settings = DataStore::getAll($_SESSION['user_id'], 'user_pref');

        $this->jsonResponse(['settings' => $settings]);
    }

    public function update()
    {
        $this->requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['key']) || !isset($data['value'])) {
            $this->jsonResponse(['error' => 'Missing key or value'], 400);
        }

        $success = DataStore::set($_SESSION['user_id'], 'user_pref', $data['key'], $data['value']);

        if ($success) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update setting'], 500);
        }
    }

    public function registerRoutes()
    {
        // Support both new and old endpoints for compatibility
        \GaiaAlpha\Router::add('GET', '/@/user/settings', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/user/settings', [$this, 'update']);

        // Global Site Settings (Admin only)
        \GaiaAlpha\Router::add('GET', '/@/admin/settings', [$this, 'getGlobal']);
        \GaiaAlpha\Router::add('POST', '/@/admin/settings', [$this, 'updateGlobal']);


    }

    private function requireAdmin()
    {
        $this->requireAuth();
        if ($_SESSION['level'] < 100) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            exit;
        }
    }

    public function getGlobal()
    {
        $this->requireAdmin();
        $settings = DataStore::getAll(0, 'global_config'); // user_id 0 = system/global
        $this->jsonResponse(['settings' => $settings]);
    }

    public function updateGlobal()
    {
        $this->requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['key']) || !isset($data['value'])) {
            $this->jsonResponse(['error' => 'Missing key or value'], 400);
        }

        // user_id 0 = system/global
        $success = DataStore::set(0, 'global_config', $data['key'], $data['value']);

        if ($success) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update setting'], 500);
        }
    }
}
