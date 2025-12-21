<?php

namespace ApiBuilder\Controller;

use GaiaAlpha\File;
use GaiaAlpha\Env;
use GaiaAlpha\Router;
use GaiaAlpha\Response;
use GaiaAlpha\Model\DB;
use GaiaAlpha\Controller\BaseController;

class ApiBuilderController extends BaseController
{
    public function registerRoutes()
    {
        Router::add('GET', '/@/admin/api-builder/tables', [$this, 'handleListTables']);
        Router::add('POST', '/@/admin/api-builder/config', [$this, 'handleSaveConfig']);
    }

    public function handleListTables()
    {
        $this->requireAdmin();
        $tables = DB::getTables();
        $config = $this->loadConfig();

        $result = [];
        foreach ($tables as $table) {
            $tableConfig = $config[$table] ?? [
                'enabled' => false,
                'methods' => ['GET'], // Default methods
                'auth_level' => 'admin' // Default auth
            ];
            $result[] = [
                'name' => $table,
                'config' => $tableConfig
            ];
        }

        Response::json($result);
    }

    public function handleSaveConfig()
    {
        $this->requireAdmin();
        $data = $this->getValidatedInput();
        if (!$data) {
            return;
        }

        $tableName = $data['name'];
        $tableConfig = $data['config'];

        // Validate table exists to be safe
        $tables = DB::getTables();
        if (!in_array($tableName, $tables)) {
            Response::json(['error' => 'Table not found'], 404);
            return;
        }

        $config = $this->loadConfig();
        $config[$tableName] = $tableConfig;

        $this->saveConfig($config);

        Response::json(['success' => true]);
    }

    private function getValidatedInput()
    {
        $data = \GaiaAlpha\Request::input();
        if (!isset($data['name']) || !isset($data['config'])) {
            Response::json(['error' => 'Invalid input'], 400);
            return null;
        }
        return $data;
    }

    private function getConfigPath()
    {
        return Env::get('path_data') . '/api-config.json';
    }

    private function loadConfig()
    {
        $path = $this->getConfigPath();
        return File::readJson($path);
    }

    private function saveConfig(array $config)
    {
        $path = $this->getConfigPath();
        File::writeJson($path, $config);
    }
}
