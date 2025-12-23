<?php

namespace Drawing\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Router;
use Drawing\Service\DrawingService;

class DrawingController extends BaseController
{
    private $service;

    public function registerRoutes()
    {
        Router::add('GET', '/@/drawing/artworks', [$this, 'index']);
        Router::add('GET', '/@/drawing/artworks/(\d+)', [$this, 'get']);
        Router::add('POST', '/@/drawing/artworks/save', [$this, 'save']);
        Router::add('DELETE', '/@/drawing/artworks/(\d+)', [$this, 'delete']);
    }

    public function __construct()
    {
        $this->service = new DrawingService();
    }

    public function index()
    {
        $artworks = $this->service->getAllArtworks();
        Response::json($artworks);
    }

    public function get($id)
    {
        $artwork = $this->service->getArtwork($id);
        if (!$artwork) {
            Response::json(['error' => 'Artwork not found'], 404);
            return;
        }

        if (is_string($artwork['content'])) {
            $artwork['content'] = json_decode($artwork['content'], true);
        }

        Response::json($artwork);
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
        $level = $data['level'] ?? 'beginner';
        $background_image = $data['background_image'] ?? null;

        $content = is_array($data['content']) || is_object($data['content'])
            ? json_encode($data['content'])
            : $data['content'];

        if (!empty($data['id'])) {
            $this->service->updateArtwork($data['id'], $title, $description, $content, $level, $background_image);
            Response::json(['success' => true, 'id' => $data['id']]);
        } else {
            $id = $this->service->createArtwork($title, $description, $content, $level, $background_image);
            Response::json(['success' => true, 'id' => $id]);
        }
    }

    public function delete($id)
    {
        $this->service->deleteArtwork($id);
        Response::json(['success' => true]);
    }
}
