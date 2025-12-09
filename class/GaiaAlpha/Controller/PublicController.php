<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\Page;

class PublicController extends BaseController
{
    public function index()
    {
        $pageModel = new Page($this->db);
        $this->jsonResponse($pageModel->getLatestPublic());
    }

    public function show($slug)
    {
        $pageModel = new Page($this->db);
        $page = $pageModel->findBySlug($slug);

        if (!$page) {
            $this->jsonResponse(['error' => 'Page not found'], 404);
        }

        $this->jsonResponse($page);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/api/public/pages', [$this, 'index']);
        \GaiaAlpha\Router::add('GET', '/api/public/pages/([\w-]+)', [$this, 'show']);
    }
}
