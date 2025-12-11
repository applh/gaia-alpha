<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Env;
use GaiaAlpha\Router;
use GaiaAlpha\Response;
use GaiaAlpha\Controller\DbController;

class ApiBuilderController extends BaseController
{
    public function registerRoutes()
    {
        Router::add('GET', '/api/admin/api-builder/tables', [$this, 'handleListTables']);
        Router::add('POST', '/api/admin/api-builder/config', [$this, 'handleSaveConfig']);
    }

    public function handleListTables()
    {
        $this->requireAdmin();
        $tables = DbController::getTables();
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

        $this->jsonResponse($result);
    }

    public function handleSaveConfig()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();
        if (!isset($data['name']) || !isset($data['config'])) {
            Response::json(['error' => 'Invalid input'], 400);
            return;
        }

        $tableName = $data['name'];
        $tableConfig = $data['config'];

        // Validate table exists to be safe
        $tables = DbController::getTables();
        if (!in_array($tableName, $tables)) {
            Response::json(['error' => 'Table not found'], 404);
            return;
        }

        $config = $this->loadConfig();
        $config[$tableName] = $tableConfig;

        $this->saveConfig($config);

        $this->jsonResponse(['success' => true]);
    }

    private function getConfigPath()
    {
        return Env::get('path_data') . '/api-config.json';
    }

    private function loadConfig()
    {
        $path = $this->getConfigPath();
        if (file_exists($path)) {
            $content = file_get_contents($path);
            return json_decode($content, true) ?? [];
        }
        return [];
    }

    private function saveConfig(array $config)
    {
        $path = $this->getConfigPath();
        file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT));
    }
}
