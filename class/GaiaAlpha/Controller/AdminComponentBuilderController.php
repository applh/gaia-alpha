<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Router;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Service\AdminComponentManager;

class AdminComponentBuilderController extends BaseController
{
    private AdminComponentManager $manager;

    public function init()
    {
        $this->manager = new AdminComponentManager();
    }

    public function registerRoutes()
    {
        Router::add('GET', '/@/admin/component-builder/list', [$this, 'handleList']);
        Router::add('GET', '/@/admin/component-builder/templates', [$this, 'handleTemplates']);
        Router::add('POST', '/@/admin/component-builder', [$this, 'handleCreate']);

        // Dynamic routes with ID need to be handled carefully with Router. 
        // Assuming Router supports regex or parameter capture.
        // Checking Router.php is needed to confirm syntax. 
        // Assuming standard /path/:id format for now based on plan.

        Router::add('GET', '/@/admin/component-builder/([0-9]+)', [$this, 'handleGet']);
        Router::add('PUT', '/@/admin/component-builder/([0-9]+)', [$this, 'handleUpdate']);
        Router::add('DELETE', '/@/admin/component-builder/([0-9]+)', [$this, 'handleDelete']);
        Router::add('POST', '/@/admin/component-builder/([0-9]+)/generate', [$this, 'handleGenerate']);
        Router::add('POST', '/@/admin/component-builder/([0-9]+)/preview', [$this, 'handlePreview']);
    }

    public function handleList()
    {
        $this->requireAdmin();
        $components = $this->manager->getComponents();
        $this->jsonResponse($components);
    }

    public function handleGet($id)
    {
        $this->requireAdmin();
        $component = $this->manager->getComponent($id);
        if (!$component) {
            $this->jsonResponse(['error' => 'Component not found'], 404);
            return;
        }

        // Decode definition if valid JSON
        if (is_string($component['definition'])) {
            $component['definition'] = json_decode($component['definition'], true);
        }

        $this->jsonResponse($component);
    }

    public function handleCreate()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();

        // Basic validation
        if (empty($data['name']) || empty($data['title'])) {
            $this->jsonResponse(['error' => 'Name and Title are required'], 400);
            return;
        }

        $data['created_by'] = $_SESSION['user_id'] ?? null;

        try {
            $id = $this->manager->createComponent($data);
            $this->jsonResponse(['id' => $id, 'success' => true]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function handleUpdate($id)
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();

        try {
            $success = $this->manager->updateComponent($id, $data);
            if ($success) {
                $this->jsonResponse(['success' => true]);
            } else {
                $this->jsonResponse(['error' => 'Update failed or no changes'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function handleDelete($id)
    {
        $this->requireAdmin();

        try {
            $success = $this->manager->deleteComponent($id);
            if ($success) {
                $this->jsonResponse(['success' => true]);
            } else {
                $this->jsonResponse(['error' => 'Delete failed'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function handleGenerate($id)
    {
        $this->requireAdmin();
        try {
            $code = $this->manager->generateCode($id);
            $this->jsonResponse(['success' => true, 'code' => $code]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function handleTemplates()
    {
        $this->requireAdmin();
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
        $this->jsonResponse($templates);
    }

    public function handlePreview($id)
    {
        $this->requireAdmin();
        // Preview logic - return the definition ?
        // Or render generic preview
        $component = $this->manager->getComponent($id);
        $this->jsonResponse(['definition' => json_decode($component['definition'])]);
    }
}
