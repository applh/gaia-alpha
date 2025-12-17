<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Database;
use GaiaAlpha\Response;
use GaiaAlpha\Model\DataStore;
use GaiaAlpha\Controller\DbController;

class SettingsController extends BaseController
{
    public function index()
    {
        $this->requireAuth();

        // We currently only store user preferences under type 'user_pref'
        $settings = DataStore::getAll(\GaiaAlpha\Session::id(), 'user_pref');

        $this->jsonResponse(['settings' => $settings]);
    }

    public function update()
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (!isset($data['key']) || !isset($data['value'])) {
            $this->jsonResponse(['error' => 'Missing key or value'], 400);
        }

        $success = DataStore::set(\GaiaAlpha\Session::id(), 'user_pref', $data['key'], $data['value']);

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

    public function getGlobal()
    {
        $this->requireAdmin();
        $settings = DataStore::getAll(0, 'global_config'); // user_id 0 = system/global
        $this->jsonResponse(['settings' => $settings]);
    }

    public function updateGlobal()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();

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
