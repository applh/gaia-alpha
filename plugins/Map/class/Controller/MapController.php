<?php

namespace Map\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Response;
use Map\Model\MapMarker;

class MapController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        Response::json(MapMarker::findAllByUserId($_SESSION['user_id']));
    }

    public function create()
    {
        $this->requireAuth();
        $this->requireAuth();
        $data = \GaiaAlpha\Request::input();

        if (empty($data['label']) || !isset($data['lat']) || !isset($data['lng'])) {
            Response::json(['error' => 'Missing required fields'], 400);
        }

        $id = MapMarker::create($_SESSION['user_id'], $data['label'], $data['lat'], $data['lng']);
        Response::json(['success' => true, 'id' => $id]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $data = \GaiaAlpha\Request::input();

        if (!isset($data['lat']) || !isset($data['lng'])) {
            Response::json(['error' => 'Missing required fields'], 400);
        }

        $success = MapMarker::updatePosition($id, $_SESSION['user_id'], $data['lat'], $data['lng']);

        if ($success) {
            Response::json(['success' => true]);
        } else {
            Response::json(['error' => 'Failed to update marker'], 500);
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/markers', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/markers', [$this, 'create']);
        \GaiaAlpha\Router::add('POST', '/@/markers/(\d+)', [$this, 'update']);
    }
}
