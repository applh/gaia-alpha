<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\File;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Model\Template;
use GaiaAlpha\Model\DB;

class TemplateController extends BaseController
{
    public function index()
    {
        if (!$this->requireAdmin())
            return;
        // Get DB Templates
        $dbTemplates = Template::findAllByUserId(\GaiaAlpha\Session::id());

        // Get File Templates
        $fileTemplates = [];
        $files = File::glob(dirname(__DIR__, 3) . '/templates/*.php');
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
        if (!$this->requireAdmin())
            return;
        // If ID starts with file_, it's a file
        if (strpos($id, 'file_') === 0) {
            $slug = substr($id, 5);
            $path = dirname(__DIR__, 3) . '/templates/' . $slug . '.php';
            if (File::exists($path)) {
                Response::json([
                    'slug' => $slug,
                    'title' => ucfirst(str_replace('_', ' ', $slug)),
                    'content' => File::read($path),
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
        if (!$this->requireAdmin())
            return;
        $data = Request::input();

        if (empty($data['title']) || empty($data['slug'])) {
            Response::json(['error' => 'Title and slug are required'], 400);
            return;
        }

        try {
            $id = Template::create(\GaiaAlpha\Session::id(), $data);
            Response::json(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Slug already exists'], 400);
        }
    }

    public function update($id)
    {
        if (!$this->requireAdmin())
            return;
        $data = Request::input();
        Template::update($id, \GaiaAlpha\Session::id(), $data);
        Response::json(['success' => true]);
    }

    public function delete($id)
    {
        if (!$this->requireAdmin())
            return;
        Template::delete($id, \GaiaAlpha\Session::id());
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
