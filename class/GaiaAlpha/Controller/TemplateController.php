<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Filesystem;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Model\Template;
use GaiaAlpha\Model\DB;

class TemplateController extends BaseController
{
    public function index()
    {
        $this->requireAdmin();
        // Get DB Templates
        $dbTemplates = Template::findAllByUserId($_SESSION['user_id']);

        // Get File Templates
        $fileTemplates = [];
        $files = Filesystem::glob(dirname(__DIR__, 3) . '/templates/*.php');
        foreach ($files as $file) {
            $slug = basename($file, '.php');
            $fileTemplates[] = [
                'id' => 'file_' . $slug,
                'title' => ucfirst(str_replace('_', ' ', $slug)) . ' (File)',
                'slug' => $slug,
                'content' => '', // Don't load content for list
                'type' => 'file',
                'created_at' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }

        // Transform DB templates to have type='db'
        $dbTemplsFormatted = array_map(function ($t) {
            $t['type'] = 'db';
            return $t;
        }, $dbTemplates);

        Response::json(array_merge($dbTemplsFormatted, $fileTemplates));
    }

    public function show($id)
    {
        $this->requireAdmin();
        // If ID starts with file_, it's a file
        if (strpos($id, 'file_') === 0) {
            $slug = substr($id, 5);
            $path = dirname(__DIR__, 3) . '/templates/' . $slug . '.php';
            if (Filesystem::exists($path)) {
                Response::json([
                    'slug' => $slug,
                    'title' => ucfirst(str_replace('_', ' ', $slug)),
                    'content' => Filesystem::read($path),
                    'type' => 'file',
                    'readonly' => true // Prevent editing files for now for safety
                ]);
            } else {
                Response::json(['error' => 'Template file not found'], 404);
            }
        } else {
            // DB Template
            $stmt = DB::query("SELECT * FROM cms_templates WHERE id = ?", [$id]);
            $tmpl = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($tmpl) {
                $tmpl['type'] = 'db';
                Response::json($tmpl);
            } else {
                Response::json(['error' => 'Template not found'], 404);
            }
        }
    }

    public function create()
    {
        $this->requireAdmin();
        $data = Request::input();

        if (empty($data['title']) || empty($data['slug'])) {
            Response::json(['error' => 'Title and slug are required'], 400);
            return;
        }

        try {
            $id = Template::create($_SESSION['user_id'], $data);
            Response::json(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Slug already exists'], 400);
        }
    }

    public function update($id)
    {
        $this->requireAdmin();
        $data = Request::input();
        Template::update($id, $_SESSION['user_id'], $data);
        Response::json(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAdmin();
        Template::delete($id, $_SESSION['user_id']);
        Response::json(['success' => true]);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/cms/templates', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/cms/templates', [$this, 'create']);
        \GaiaAlpha\Router::add('GET', '/@/cms/templates/(\w+)', [$this, 'show']);
        \GaiaAlpha\Router::add('PATCH', '/@/cms/templates/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', '/@/cms/templates/(\d+)', [$this, 'delete']);
    }
}
