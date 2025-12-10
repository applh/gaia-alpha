<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\MapMarker;

class MapController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        $markerModel = new MapMarker($this->db);
        $this->jsonResponse($markerModel->findAllByUserId($_SESSION['user_id']));
    }

    public function create()
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (empty($data['label']) || !isset($data['lat']) || !isset($data['lng'])) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }

        $markerModel = new MapMarker($this->db);
        $id = $markerModel->create($_SESSION['user_id'], $data['label'], $data['lat'], $data['lng']);
        $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (!isset($data['lat']) || !isset($data['lng'])) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }

        $markerModel = new MapMarker($this->db);
        $success = $markerModel->updatePosition($id, $_SESSION['user_id'], $data['lat'], $data['lng']);

        if ($success) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update marker'], 500);
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/api/markers', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/api/markers', [$this, 'create']);
        \GaiaAlpha\Router::add('POST', '/api/markers/(\d+)', [$this, 'update']);
    }
}
