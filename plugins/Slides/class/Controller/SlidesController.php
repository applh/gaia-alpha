<?php

namespace Slides\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Router;
use Slides\Service\SlidesService;

class SlidesController extends BaseController
{
    private $service;

    public function registerRoutes()
    {
        // Deck routes
        Router::add('GET', '/@/slides/list', [$this, 'list']);
        Router::add('GET', '/@/slides/deck/(\d+)', [$this, 'getDeck']);
        Router::add('POST', '/@/slides/deck/save', [$this, 'saveDeck']);
        Router::add('DELETE', '/@/slides/deck/(\d+)', [$this, 'deleteDeck']);

        // Page routes
        Router::add('GET', '/@/slides/deck/(\d+)/pages', [$this, 'getPages']);
        Router::add('POST', '/@/slides/deck/(\d+)/pages/add', [$this, 'addPage']);
        Router::add('POST', '/@/slides/pages/(\d+)/update', [$this, 'updatePage']);
        Router::add('DELETE', '/@/slides/pages/(\d+)', [$this, 'deletePage']);
        Router::add('POST', '/@/slides/deck/(\d+)/pages/reorder', [$this, 'reorderPages']);
    }

    public function __construct()
    {
        $this->service = new SlidesService();
    }

    public function list()
    {
        $decks = $this->service->getAllDecks();
        Response::json($decks);
    }

    public function getDeck($id)
    {
        $deck = $this->service->getDeck($id);
        if (!$deck) {
            Response::json(['error' => 'Slide deck not found'], 404);
            return;
        }
        Response::json($deck);
    }

    public function saveDeck()
    {
        $data = Request::input();
        if (empty($data['title'])) {
            Response::json(['error' => 'Title is required'], 400);
            return;
        }

        if (!empty($data['id'])) {
            $this->service->updateDeck($data['id'], $data['title']);
            Response::json(['success' => true, 'id' => $data['id']]);
        } else {
            $id = $this->service->createDeck($data['title']);
            Response::json(['success' => true, 'id' => $id]);
        }
    }

    public function deleteDeck($id)
    {
        $this->service->deleteDeck($id);
        Response::json(['success' => true]);
    }

    public function getPages($deck_id)
    {
        $pages = $this->service->getPages($deck_id);
        foreach ($pages as &$page) {
            if (is_string($page['content'])) {
                $page['content'] = json_decode($page['content'], true);
            }
        }
        Response::json($pages);
    }

    public function addPage($deck_id)
    {
        $data = Request::input();
        $content = isset($data['content']) ? (is_array($data['content']) ? json_encode($data['content']) : $data['content']) : '[]';
        $slide_type = $data['slide_type'] ?? 'drawing';
        $order_index = $data['order_index'] ?? null;

        $id = $this->service->addPage($deck_id, $content, $slide_type, $order_index);
        Response::json(['success' => true, 'id' => $id]);
    }

    public function updatePage($id)
    {
        $data = Request::input();
        $content = isset($data['content']) ? (is_array($data['content']) ? json_encode($data['content']) : $data['content']) : null;
        $slide_type = $data['slide_type'] ?? null;

        if ($content === null) {
            Response::json(['error' => 'Content is required'], 400);
            return;
        }

        $this->service->updatePage($id, $content, $slide_type);
        Response::json(['success' => true]);
    }

    public function deletePage($id)
    {
        $this->service->deletePage($id);
        Response::json(['success' => true]);
    }

    public function reorderPages($deck_id)
    {
        $data = Request::input();
        if (empty($data['page_ids']) || !is_array($data['page_ids'])) {
            Response::json(['error' => 'page_ids array is required'], 400);
            return;
        }

        $this->service->reorderPages($deck_id, $data['page_ids']);
        Response::json(['success' => true]);
    }
}
