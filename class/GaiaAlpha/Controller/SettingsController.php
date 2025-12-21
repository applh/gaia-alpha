<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Database;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Model\DataStore;
use GaiaAlpha\Controller\DbController;

class SettingsController extends BaseController
{
    public function index()
    {
        if (!$this->requireAuth())
            return;

        // We currently only store user preferences under type 'user_pref'
        $settings = DataStore::getAll(\GaiaAlpha\Session::id(), 'user_pref');

        Response::json(['settings' => $settings]);
    }

    public function update()
    {
        if (!$this->requireAuth())
            return;
        $data = Request::input();

        if (!isset($data['key']) || !isset($data['value'])) {
            Response::json(['error' => 'Missing key or value'], 400);
            return;
        }

        $success = DataStore::set(\GaiaAlpha\Session::id(), 'user_pref', $data['key'], $data['value']);

        if ($success) {
            Response::json(['success' => true]);
        } else {
            Response::json(['error' => 'Failed to update setting'], 500);
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
        if (!$this->requireAdmin())
            return;
        $settings = DataStore::getAll(0, 'global_config'); // user_id 0 = system/global
        Response::json(['settings' => $settings]);
    }

    public function updateGlobal()
    {
        if (!$this->requireAdmin())
            return;
        $data = Request::input();

        if (!isset($data['key']) || !isset($data['value'])) {
            Response::json(['error' => 'Missing key or value'], 400);
            return;
        }

        // user_id 0 = system/global
        $success = DataStore::set(0, 'global_config', $data['key'], $data['value']);

        if ($success) {
            Response::json(['success' => true]);
        } else {
            Response::json(['error' => 'Failed to update setting'], 500);
        }
    }
}
