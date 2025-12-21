<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\Page;
use GaiaAlpha\Model\Template;
use GaiaAlpha\Response;
use GaiaAlpha\Request;

class CmsController extends BaseController
{
    public function index()
    {
        if (!$this->requireAuth())
            return;
        $cat = Request::query('cat', 'page');
        Response::json(Page::findAllByUserId(\GaiaAlpha\Session::id(), $cat));
    }

    public function create()
    {
        $this->requireAuth();
        $data = Request::input();

        if (empty($data['title']) || empty($data['slug'])) {
            Response::json(['error' => 'Missing title or slug'], 400);
            return;
        }

        // Default cat to 'page' if not provided
        if (empty($data['cat'])) {
            $data['cat'] = 'page';
        }

        try {
            $id = Page::create(\GaiaAlpha\Session::id(), $data);
            Response::json(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Slug already exists'], 400);
        }
    }

    public function update($id)
    {
        if (!$this->requireAuth())
            return;
        $data = Request::input();
        Page::update($id, \GaiaAlpha\Session::id(), $data);
        Response::json(['success' => true]);
    }

    public function delete($id)
    {
        if (!$this->requireAuth())
            return;
        Page::delete($id, \GaiaAlpha\Session::id());
        Response::json(['success' => true]);
    }

    public function upload()
    {
        if (!$this->requireAuth())
            return;
        if (!Request::hasFile('image')) {
            Response::json(['error' => 'No image uploaded or upload error'], 400);
            return;
        }

        try {
            $media = new \GaiaAlpha\Media(\GaiaAlpha\Env::get('path_data'));
            $result = $media->upload(Request::file('image'), \GaiaAlpha\Session::id());

            $filename = $result['filename'];
            $mediaUrl = $result['url'];

            // Auto-create a CMS page entry for this image localy
            // cat="image", title=original name, slug=uniqid, image=url
            $imageSlug = 'img-' . pathinfo($filename, PATHINFO_FILENAME);

            try {
                Page::create(\GaiaAlpha\Session::id(), [
                    'title' => Request::file('image')['name'],
                    'slug' => $imageSlug,
                    'cat' => 'image',
                    'image' => $mediaUrl,
                    'content' => '',
                    'tag' => 'upload'
                ]);
            } catch (\Exception $e) {
                // If slug exists, try appending something random
                try {
                    Page::create(\GaiaAlpha\Session::id(), [
                        'title' => Request::file('image')['name'],
                        'slug' => $imageSlug . '-' . rand(1000, 9999),
                        'cat' => 'image',
                        'image' => $mediaUrl,
                        'content' => '',
                        'tag' => 'upload'
                    ]);
                } catch (\Exception $ex) {
                    // Ignore
                }
            }

            Response::json(['url' => $mediaUrl]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/cms/pages', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/cms/pages', [$this, 'create']);
        \GaiaAlpha\Router::add('PATCH', '/@/cms/pages/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', '/@/cms/pages/(\d+)', [$this, 'delete']);
        \GaiaAlpha\Router::add('POST', '/@/cms/upload', [$this, 'upload']);

        // Template Routes
        \GaiaAlpha\Router::add('GET', '/@/cms/templates', [$this, 'getTemplates']);
        \GaiaAlpha\Router::add('POST', '/@/cms/templates', [$this, 'createTemplate']);
        \GaiaAlpha\Router::add('PATCH', '/@/cms/templates/(\d+)', [$this, 'updateTemplate']);
        \GaiaAlpha\Router::add('DELETE', '/@/cms/templates/(\d+)', [$this, 'deleteTemplate']);
    }

    public function getTemplates()
    {
        if (!$this->requireAuth())
            return;
        Response::json(Template::findAllByUserId(\GaiaAlpha\Session::id()));
    }

    public function createTemplate()
    {
        if (!$this->requireAuth())
            return;
        $data = Request::input();

        if (empty($data['title']) || empty($data['slug'])) {
            Response::json(['error' => 'Missing title or slug'], 400);
            return;
        }

        try {
            $id = Template::create(\GaiaAlpha\Session::id(), $data);
            Response::json(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Slug already exists'], 400);
        }
    }

    public function updateTemplate($id)
    {
        if (!$this->requireAuth())
            return;
        $data = Request::input();
        Template::update($id, \GaiaAlpha\Session::id(), $data);
        Response::json(['success' => true]);
    }

    public function deleteTemplate($id)
    {
        if (!$this->requireAuth())
            return;
        Template::delete($id, \GaiaAlpha\Session::id());
        Response::json(['success' => true]);
    }

}
