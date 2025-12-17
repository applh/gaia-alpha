<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Model\DB;

class PartialController extends BaseController
{
    public function index()
    {
        $this->requireAdmin();
        $stmt = DB::query("SELECT * FROM cms_partials ORDER BY name ASC");
        Response::json($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function create()
    {
        $this->requireAdmin();
        $data = Request::input();
        if (empty($data['name'])) {
            Response::json(['error' => 'Name is required'], 400);
            return;
        }

        try {
            DB::execute(
                "INSERT INTO cms_partials (user_id, name, content, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$_SESSION['user_id'], $data['name'], $data['content'] ?? '']
            );
            Response::json(['success' => true, 'id' => DB::lastInsertId()]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Name already exists'], 400);
        }
    }

    public function update($id)
    {
        $this->requireAdmin();
        $data = Request::input();

        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $values[] = $data['name'];
        }
        if (isset($data['content'])) {
            $fields[] = "content = ?";
            $values[] = $data['content'];
        }

        if (empty($fields)) {
            Response::json(['success' => true]);
            return;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $id;

        $sql = "UPDATE cms_partials SET " . implode(', ', $fields) . " WHERE id = ?";
        DB::execute($sql, $values);
        Response::json(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAdmin();
        DB::execute("DELETE FROM cms_partials WHERE id = ? AND user_id = ?", [$id, $_SESSION['user_id']]);
        Response::json(['success' => true]);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/cms/partials', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/cms/partials', [$this, 'create']);
        \GaiaAlpha\Router::add('PATCH', '/@/cms/partials/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', '/@/cms/partials/(\d+)', [$this, 'delete']);
    }
}
