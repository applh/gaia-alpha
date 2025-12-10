<?php
namespace GaiaAlpha\Controller;

use GaiaAlpha\Framework;
use GaiaAlpha\Router;
use GaiaAlpha\Model\Menu;

class MenuController
{

    public function registerRoutes()
    {
        Router::get('/api/menus', [$this, 'list']);
        Router::post('/api/menus', [$this, 'create']);
        Router::get('/api/menus/(\d+)', [$this, 'get']);
        Router::patch('/api/menus/(\d+)', [$this, 'update']);
        Router::delete('/api/menus/(\d+)', [$this, 'delete']);
    }

    public function list()
    {
        if (!Framework::checkAuth(100))
            return; // Admin only
        $menus = Menu::all();
        Framework::json($menus);
    }

    public function get($id)
    {
        if (!Framework::checkAuth(100))
            return;
        $menu = Menu::find($id);
        if (!$menu) {
            Framework::json(['error' => 'Not found'], 404);
            return;
        }
        Framework::json($menu);
    }

    public function create()
    {
        if (!Framework::checkAuth(100))
            return;
        $data = Framework::decodeBody();

        if (empty($data['title'])) {
            Framework::json(['error' => 'Title is required'], 400);
            return;
        }

        // Ensure items is a valid JSON string or empty
        if (isset($data['items']) && !is_string($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        $id = Menu::create($data);
        Framework::json(['id' => $id, 'message' => 'Menu created'], 201);
    }

    public function update($id)
    {
        if (!Framework::checkAuth(100))
            return;
        $menu = Menu::find($id);
        if (!$menu) {
            Framework::json(['error' => 'Not found'], 404);
            return;
        }

        $data = Framework::decodeBody();

        // Prevent items from being double-encoded if passed as array
        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        Menu::update($id, $data);
        Framework::json(['message' => 'Menu updated']);
    }

    public function delete($id)
    {
        if (!Framework::checkAuth(100))
            return;
        Menu::delete($id);
        Framework::json(['message' => 'Menu deleted']);
    }
}
