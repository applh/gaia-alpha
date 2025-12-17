<?php

namespace Map\Controller;

use GaiaAlpha\Controller\BaseController;
use Map\Model\MapMarker;

class MapController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        $this->jsonResponse(MapMarker::findAllByUserId($_SESSION['user_id']));
    }

    public function create()
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (empty($data['label']) || !isset($data['lat']) || !isset($data['lng'])) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }

        $id = MapMarker::create($_SESSION['user_id'], $data['label'], $data['lat'], $data['lng']);
        $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (!isset($data['lat']) || !isset($data['lng'])) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }

        $success = MapMarker::updatePosition($id, $_SESSION['user_id'], $data['lat'], $data['lng']);

        if ($success) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update marker'], 500);
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/markers', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/markers', [$this, 'create']);
        \GaiaAlpha\Router::add('POST', '/@/markers/(\d+)', [$this, 'update']);
    }
}
