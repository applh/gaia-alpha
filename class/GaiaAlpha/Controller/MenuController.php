<?php
namespace GaiaAlpha\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Framework;
use GaiaAlpha\Router;
use GaiaAlpha\Response;
use GaiaAlpha\Model\Menu;

class MenuController extends BaseController
{

    public function registerRoutes()
    {
        Router::get('/@/menus', [$this, 'list']);
        Router::post('/@/menus', [$this, 'create']);
        Router::get('/@/menus/(\d+)', [$this, 'get']);
        Router::patch('/@/menus/(\d+)', [$this, 'update']);
        Router::delete('/@/menus/(\d+)', [$this, 'delete']);
    }

    public function list()
    {
        \GaiaAlpha\Session::requireLevel(100);
        $menus = Menu::all();
        Response::json($menus);
    }

    public function get($id)
    {
        \GaiaAlpha\Session::requireLevel(100);
        $menu = Menu::find($id);
        if (!$menu) {
            Response::json(['error' => 'Not found'], 404);
            return;
        }
        Response::json($menu);
    }

    public function create()
    {
        \GaiaAlpha\Session::requireLevel(100);
        $data = \GaiaAlpha\Request::input();

        if (empty($data['title'])) {
            Response::json(['error' => 'Title is required'], 400);
            return;
        }

        // Ensure items is a valid JSON string or empty
        if (isset($data['items']) && !is_string($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        $id = Menu::create($data);
        Response::json(['id' => $id, 'message' => 'Menu created'], 201);
    }

    public function update($id)
    {
        \GaiaAlpha\Session::requireLevel(100);
        $menu = Menu::find($id);
        if (!$menu) {
            Response::json(['error' => 'Not found'], 404);
            return;
        }

        $data = \GaiaAlpha\Request::input();

        // Prevent items from being double-encoded if passed as array
        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        Menu::update($id, $data);
        Response::json(['message' => 'Menu updated']);
    }

    public function delete($id)
    {
        \GaiaAlpha\Session::requireLevel(100);
        Menu::delete($id);
        Response::json(['message' => 'Menu deleted']);
    }
}
