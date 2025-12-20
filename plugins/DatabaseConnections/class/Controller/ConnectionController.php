<?php

namespace DatabaseConnections\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;
use DatabaseConnections\Service\ConnectionManager;
use Exception;

class ConnectionController extends BaseController
{
    private $connectionManager;

    public function __construct()
    {
        $this->connectionManager = new ConnectionManager();
    }

    public function listConnections()
    {
        $this->requireAdmin();
        $connections = $this->connectionManager->listConnections();
        \GaiaAlpha\Response::json(['connections' => $connections]);
    }

    public function getConnection($id)
    {
        $this->requireAdmin();
        $connection = $this->connectionManager->getConnection($id);

        if (!$connection) {
            \GaiaAlpha\Response::json(['error' => 'Connection not found'], 404);
            return;
        }

        unset($connection['password']); // Mask password
        \GaiaAlpha\Response::json(['connection' => $connection]);
    }

    public function saveConnection()
    {
        $this->requireAdmin();
        $data = \GaiaAlpha\Request::input();

        try {
            $id = $this->connectionManager->saveConnection($data);
            \GaiaAlpha\Response::json(['success' => true, 'id' => $id]);
        } catch (Exception $e) {
            \GaiaAlpha\Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteConnection($id)
    {
        $this->requireAdmin();
        $this->connectionManager->deleteConnection($id);
        \GaiaAlpha\Response::json(['success' => true]);
    }

    public function testConnection()
    {
        $this->requireAdmin();
        $data = \GaiaAlpha\Request::input();

        try {
            $this->connectionManager->testConnection($data);
            \GaiaAlpha\Response::json(['success' => true]);
        } catch (Exception $e) {
            \GaiaAlpha\Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function executeQuery($id)
    {
        $this->requireAdmin();
        $data = \GaiaAlpha\Request::input();
        $sql = $data['query'] ?? '';

        if (empty($sql)) {
            \GaiaAlpha\Response::json(['error' => 'Query is required'], 400);
            return;
        }

        try {
            $result = $this->connectionManager->executeQuery($id, $sql);
            \GaiaAlpha\Response::json(['success' => true, 'result' => $result]);
        } catch (Exception $e) {
            \GaiaAlpha\Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function registerRoutes()
    {
        Router::add('GET', '/@/admin/db-connections', [$this, 'listConnections']);
        Router::add('GET', '/@/admin/db-connections/(\d+)', [$this, 'getConnection']);
        Router::add('POST', '/@/admin/db-connections', [$this, 'saveConnection']);
        Router::add('DELETE', '/@/admin/db-connections/(\d+)', [$this, 'deleteConnection']);
        Router::add('POST', '/@/admin/db-connections/test', [$this, 'testConnection']);
        Router::add('POST', '/@/admin/db-connections/(\d+)/query', [$this, 'executeQuery']);
    }
}
