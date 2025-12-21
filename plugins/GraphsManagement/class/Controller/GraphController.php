<?php

namespace GraphsManagement\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Session;
use GraphsManagement\Service\GraphService;

class GraphController extends BaseController
{
    public function registerRoutes()
    {
        // Graph management
        Router::add('GET', '/@/graphs', [$this, 'listGraphs']);
        Router::add('GET', '/@/graphs/(\d+)', [$this, 'getGraph']);
        Router::add('POST', '/@/graphs', [$this, 'createGraph']);
        Router::add('PUT', '/@/graphs/(\d+)', [$this, 'updateGraph']);
        Router::add('DELETE', '/@/graphs/(\d+)', [$this, 'deleteGraph']);
        Router::add('GET', '/@/graphs/(\d+)/data', [$this, 'getGraphData']);

        // Collection management
        Router::add('GET', '/@/graphs/collections', [$this, 'listCollections']);
        Router::add('GET', '/@/graphs/collections/(\d+)', [$this, 'getCollection']);
        Router::add('POST', '/@/graphs/collections', [$this, 'createCollection']);
        Router::add('PUT', '/@/graphs/collections/(\d+)', [$this, 'updateCollection']);
        Router::add('DELETE', '/@/graphs/collections/(\d+)', [$this, 'deleteCollection']);

        // Public embed endpoint
        Router::add('GET', '/@/graphs/(\d+)/embed', [$this, 'getEmbedData']);
    }

    /**
     * List all graphs for current user
     */
    public function listGraphs()
    {
        if (!$this->requireAuth())
            return;

        $filters = [];
        if ($chartType = Request::input('chart_type')) {
            $filters['chart_type'] = $chartType;
        }
        if ($search = Request::input('search')) {
            $filters['search'] = $search;
        }
        if ($limit = Request::input('limit')) {
            $filters['limit'] = (int) $limit;
        }

        $userId = Session::id();
        $graphs = GraphService::listGraphs($userId, $filters);

        Response::json($graphs);
    }

    /**
     * Get a specific graph
     */
    public function getGraph($id)
    {
        if (!$this->requireAuth())
            return;

        $graph = GraphService::getGraph((int) $id);

        if (!$graph) {
            Response::json(['error' => 'Graph not found'], 404);
            return;
        }

        $userId = Session::id();
        if ($graph['user_id'] != $userId) {
            Response::json(['error' => 'Access denied'], 403);
            return;
        }

        Response::json($graph);
    }

    /**
     * Create a new graph
     */
    public function createGraph()
    {
        if (!$this->requireAuth())
            return;

        $input = Request::input();

        try {
            $graph = GraphService::createGraph($input);
            Response::json($graph, 201);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update a graph
     */
    public function updateGraph($id)
    {
        if (!$this->requireAuth())
            return;

        $input = Request::input();

        try {
            GraphService::updateGraph((int) $id, $input);
            $graph = GraphService::getGraph((int) $id);
            Response::json($graph);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a graph
     */
    public function deleteGraph($id)
    {
        if (!$this->requireAuth())
            return;

        try {
            GraphService::deleteGraph((int) $id);
            Response::json(['success' => true]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get graph data
     */
    public function getGraphData($id)
    {
        if (!$this->requireAuth())
            return;

        try {
            $graph = GraphService::getGraph((int) $id);

            if (!$graph) {
                Response::json(['error' => 'Graph not found'], 404);
                return;
            }

            $userId = Session::id();
            if ($graph['user_id'] != $userId) {
                Response::json(['error' => 'Access denied'], 403);
                return;
            }

            $data = GraphService::fetchGraphData((int) $id);
            Response::json([
                'graph' => $graph,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * List collections
     */
    public function listCollections()
    {
        if (!$this->requireAuth())
            return;

        $userId = Session::id();
        $collections = GraphService::listCollections($userId);

        Response::json($collections);
    }

    /**
     * Get a specific collection
     */
    public function getCollection($id)
    {
        if (!$this->requireAuth())
            return;

        $collection = GraphService::getCollection((int) $id);

        if (!$collection) {
            Response::json(['error' => 'Collection not found'], 404);
            return;
        }

        $userId = Session::id();
        if ($collection['user_id'] != $userId) {
            Response::json(['error' => 'Access denied'], 403);
            return;
        }

        Response::json($collection);
    }

    /**
     * Create a collection
     */
    public function createCollection()
    {
        if (!$this->requireAuth())
            return;

        $input = Request::input();

        try {
            $collection = GraphService::createCollection($input);
            Response::json($collection, 201);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update a collection
     */
    public function updateCollection($id)
    {
        if (!$this->requireAuth())
            return;

        $input = Request::input();

        try {
            GraphService::updateCollection((int) $id, $input);
            $collection = GraphService::getCollection((int) $id);
            Response::json($collection);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a collection
     */
    public function deleteCollection($id)
    {
        if (!$this->requireAuth())
            return;

        try {
            GraphService::deleteCollection((int) $id);
            Response::json(['success' => true]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get public embed data (no auth required)
     */
    public function getEmbedData($id)
    {
        try {
            $graph = GraphService::getGraph((int) $id);

            if (!$graph) {
                Response::json(['error' => 'Graph not found'], 404);
                return;
            }

            if (!$graph['is_public']) {
                Response::json(['error' => 'This graph is not public'], 403);
                return;
            }

            $data = GraphService::fetchGraphData((int) $id);
            Response::json([
                'graph' => $graph,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
