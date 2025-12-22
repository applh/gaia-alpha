<?php

namespace ApiAnalytics\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Response;
use GaiaAlpha\Router;
use GaiaAlpha\Request;
use ApiAnalytics\Service\ApiStatsService;

class ApiStatsController extends BaseController
{
    public function registerRoutes()
    {
        Router::get('/@/api-analytics/stats', [$this, 'getStats']);
        Router::get('/@/api-analytics/logs', [$this, 'getLogs']);
    }

    public function getStats()
    {
        if (!$this->requireAdmin())
            return;

        $days = Request::queryInt('days', 30);
        $stats = ApiStatsService::getSummary($days);

        Response::json($stats);
    }

    public function getLogs()
    {
        if (!$this->requireAdmin())
            return;

        $limit = Request::queryInt('limit', 50);
        $logs = ApiStatsService::getRecentLogs($limit);

        Response::json($logs);
    }
}
