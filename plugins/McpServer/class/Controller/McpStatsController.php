<?php

namespace McpServer\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Response;
use GaiaAlpha\Session;
use McpServer\Service\McpStatsService;

class McpStatsController extends BaseController
{
    /**
     * Get MCP stats
     * GET /@/mcp/stats
     */
    public function getStats()
    {
        if (!Session::isAdmin()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        try {
            $stats = McpStatsService::getSummary();
            Response::json($stats);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Register routes
     */
    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/mcp/stats', [$this, 'getStats']);
    }
}
