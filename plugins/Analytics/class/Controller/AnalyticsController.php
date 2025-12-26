<?php

namespace Analytics\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;
use GaiaAlpha\Response;
use Analytics\Service\AnalyticsService;

class AnalyticsController extends BaseController
{
    public function registerRoutes()
    {
        foreach (Router::allDashPrefixes() as $prefix) {
            Router::add('GET', $prefix . '/analytics/stats', [$this, 'getStats']);
        }
    }

    public function getStats()
    {
        $this->requireAdmin();
        $stats = AnalyticsService::getStats();
        Response::json($stats);
    }
}
