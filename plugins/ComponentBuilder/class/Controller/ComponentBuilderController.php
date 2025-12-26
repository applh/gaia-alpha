<?php


namespace ComponentBuilder\Controller;

use GaiaAlpha\Router;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Controller\BaseController;
use ComponentBuilder\Service\ComponentBuilderManager;


class ComponentBuilderController extends BaseController
{
    private ComponentBuilderManager $manager;

    public function init()
    {
        $this->manager = new ComponentBuilderManager();
    }

    public function registerRoutes()
    {
        $prefix = Router::adminPrefix();

        Router::add('GET', $prefix . '/component-builder/list', [$this, 'handleList']);
        Router::add('GET', $prefix . '/component-builder/templates', [$this, 'handleTemplates']);
        Router::add('POST', $prefix . '/component-builder', [$this, 'handleCreate']);

        // Dynamic routes
        Router::add('GET', $prefix . '/component-builder/([0-9]+)', [$this, 'handleGet']);
        Router::add('PUT', $prefix . '/component-builder/([0-9]+)', [$this, 'handleUpdate']);
        Router::add('DELETE', $prefix . '/component-builder/([0-9]+)', [$this, 'handleDelete']);
        Router::add('POST', $prefix . '/component-builder/([0-9]+)/generate', [$this, 'handleGenerate']);
        Router::add('POST', $prefix . '/component-builder/([0-9]+)/preview', [$this, 'handlePreview']); // Keep logic API
        Router::add('GET', '/component-preview/([a-zA-Z0-9_-]+)', [$this, 'handlePreviewRender']); // Standalone Viewer
    }

    public function handleList()
    {
        if (!$this->requireAdmin())
            return;
        $components = $this->manager->getComponents();
        Response::json($components);
    }

    // ... (rest of methods)

    public function handlePreviewRender($viewName)
    {
        if (!$this->requireAdmin())
            return;

        // Sanitize
        $viewName = preg_replace('/[^a-zA-Z0-9_-]/', '', $viewName);

        // Find component by view_name (inefficiently for now, or assume it exists)
        // Ideally Manager should have findByViewName
        $components = $this->manager->getComponents();
        $component = null;
        foreach ($components as $c) {
            if ($c['view_name'] == $viewName) {
                $component = $c;
                break;
            }
        }

        if (!$component) {
            // Fallback: maybe passed ID? 
            $component = $this->manager->getComponent($viewName);
        }

        if (!$component) {
            http_response_code(404);
            echo "Component not found";
            return;
        }

        $name = $component['name'];
        // $viewName is already set

        require __DIR__ . '/../../views/component_viewer.php';
    }

    public function handlePreview($id)
    {
        if (!$this->requireAdmin())
            return;
        $component = $this->manager->getComponent($id);
        Response::json(['definition' => json_decode($component['definition'])]);
    }

    public function handleGet($id)
    {
        if (!$this->requireAdmin())
            return;
        $component = $this->manager->getComponent($id);
        if (!$component) {
            Response::json(['error' => 'Component not found'], 404);
            return;
        }

        // Decode definition if valid JSON
        if (is_string($component['definition'])) {
            $component['definition'] = json_decode($component['definition'], true);
        }

        Response::json($component);
    }

    public function handleCreate()
    {
        if (!$this->requireAdmin())
            return;
        $data = Request::input();

        // Basic validation
        if (empty($data['name']) || empty($data['title'])) {
            Response::json(['error' => 'Name and Title are required'], 400);
            return;
        }

        $data['created_by'] = $_SESSION['user_id'] ?? null;

        try {
            $id = $this->manager->createComponent($data);
            Response::json(['id' => $id, 'success' => true]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleUpdate($id)
    {
        if (!$this->requireAdmin())
            return;
        $data = Request::input();

        try {
            $success = $this->manager->updateComponent($id, $data);
            if ($success) {
                Response::json(['success' => true]);
            } else {
                Response::json(['error' => 'Update failed or no changes'], 400);
            }
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleDelete($id)
    {
        if (!$this->requireAdmin())
            return;

        try {
            $success = $this->manager->deleteComponent($id);
            if ($success) {
                Response::json(['success' => true]);
            } else {
                Response::json(['error' => 'Delete failed'], 400);
            }
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleGenerate($id)
    {
        if (!$this->requireAdmin())
            return;
        try {
            $code = $this->manager->generateCode($id);
            Response::json(['success' => true, 'code' => $code]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleTemplates()
    {
        if (!$this->requireAdmin())
            return;
        // Return hardcoded templates for now
        $templates = [
            [
                'name' => 'Blank',
                'description' => 'Empty component',
                'layout' => ['type' => 'container', 'children' => []]
            ],
            [
                'name' => 'CRUD Table',
                'description' => 'Table with actions',
                'layout' => [
                    'type' => 'container',
                    'children' => [
                        ['type' => 'data-table']
                    ]
                ]
            ]
        ];
        Response::json($templates);
    }
}
