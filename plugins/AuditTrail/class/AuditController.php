<?php

namespace AuditTrail;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Response;
use GaiaAlpha\Model\DB;
use GaiaAlpha\Request;
use GaiaAlpha\Session;

class AuditController extends BaseController
{
    public function index()
    {
        if (!$this->requireAdmin())
            return;

        // Render the admin view (usually via a Template or just returning JSON/HTML)
        // Since Gaia Alpha uses Vue, we might just output the shell or the JS component.
        // But for a plugin page, we usually have a route that renders the main layout with the plugin component.

        // Check how other plugins do it. 
        // For now, let's assume we return a view or just the JSON for the API.

        // This method might just facilitate the "view" if using server-side rendering,
        // but typically we want an API for the data.
    }

    public function getLogs()
    {
        if (!$this->requireAdmin())
            return;

        $page = Request::queryInt('page', 1);
        $limit = Request::queryInt('limit', 50);
        $offset = ($page - 1) * $limit;

        // Filters
        $userId = Request::query('user_id');
        $action = Request::query('action');

        $where = ["1=1"];
        $params = [];

        if ($userId) {
            $where[] = "user_id = :user_id";
            $params['user_id'] = $userId;
        }
        if ($action) {
            $where[] = "action LIKE :action";
            $params['action'] = "%$action%";
        }

        $whereStr = implode(' AND ', $where);

        $sql = "SELECT * FROM cms_audit_logs WHERE $whereStr ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

        // DB::fetchAll uses wrapper around fetchAll
        // But need to ensure we can pass named params to fetchAll if it just calls query()
        // DB::fetchAll($sql, $params) -> DB::query($sql, $params)->fetchAll()

        $logs = DB::fetchAll($sql, $params);

        $countSql = "SELECT COUNT(*) FROM cms_audit_logs WHERE $whereStr";
        $total = DB::fetchColumn($countSql, $params);

        Response::json([
            'data' => $logs,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::get('/api/audit-logs', [$this, 'getLogs']);
    }
}
