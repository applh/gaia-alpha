<?php

namespace NodeEditor\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Router;
use NodeEditor\Service\NodeEditorService;

class NodeEditorController extends BaseController
{

    private $service;

    public function registerRoutes()
    {
        Router::add('GET', '/@/node_editor/diagrams', [$this, 'index']);
        Router::add('GET', '/@/node_editor/diagrams/(\d+)', [$this, 'get']);
        Router::add('POST', '/@/node_editor/diagrams/save', [$this, 'save']);
        Router::add('DELETE', '/@/node_editor/diagrams/(\d+)', [$this, 'delete']);
    }

    public function __construct()
    {
        $this->service = new NodeEditorService();
        // Ensure table exists on first run (simple migration for now, ideal would be full migration system)
        // In production, this should be handled by a proper migration runner.
        // For now, we'll execute the schema if table doesn't exist? 
        // Or user runs migration manually. Let's stick to just the controller logic.
        // The user/system should run the schema.
    }

    public function index()
    {
        // GET /@/node_editor/diagrams
        $diagrams = $this->service->getAllDiagrams();
        Response::json($diagrams);
    }

    public function get($id)
    {
        $diagram = $this->service->getDiagram($id);
        if (!$diagram) {
            Response::json(['error' => 'Diagram not found'], 404);
            return;
        }

        // Decode content if it is a string, to return proper JSON object structure if desired, 
        // but frontend might expect string or object. Let's return as is or decode?
        // Usually better to return as structured data.
        if (is_string($diagram['content'])) {
            $diagram['content'] = json_decode($diagram['content'], true);
        }

        Response::json($diagram);
    }

    public function save()
    {
        $data = Request::input();

        if (empty($data['title'])) {
            Response::json(['error' => 'Title is required'], 400);
            return;
        }

        $title = $data['title'];
        $description = $data['description'] ?? '';

        // Content should be encoded to JSON string for storage
        $content = is_array($data['content']) || is_object($data['content'])
            ? json_encode($data['content'])
            : $data['content'];

        if (!empty($data['id'])) {
            $this->service->updateDiagram($data['id'], $title, $description, $content);
            Response::json(['success' => true, 'id' => $data['id']]);
        } else {
            $id = $this->service->createDiagram($title, $description, $content);
            Response::json(['success' => true, 'id' => $id]);
        }
    }

    public function delete($id)
    {
        $this->service->deleteDiagram($id);
        Response::json(['success' => true]);
    }
}
